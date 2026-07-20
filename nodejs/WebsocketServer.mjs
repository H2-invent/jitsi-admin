/**
 * jitsi-admin WebSocket Server — Main Entry Point
 * ================================================
 *
 * This file is the single entry point for the real-time WebSocket infrastructure.
 * It starts an HTTP(S) server and attaches a Socket.IO engine that handles
 * bidirectional communication between browser clients and the PHP backend.
 *
 * It serves FIVE distinct functions in one process:
 *
 *   1. HTTP SERVER (TLS auto-detection)
 *      — Listens on `PORT` (default 3000)
 *      — Auto-detects TLS cert/key files; starts HTTPS if valid PEMs exist,
 *        otherwise falls back to plain HTTP
 *      — Serves the Mercure bridge endpoint and health check routes
 *
 *   2. SOCKET.IO SERVER (real-time bidirectional connections)
 *      — Mounted at path `/ws`
 *      — Authenticates browser clients via JWT (signed with WEBSOCKET_SECRET)
 *      — Joins clients to Socket.IO rooms specified in their JWT `rooms` claim
 *      — Routes all client-emitted events to websocketState.mjs for dispatch
 *
 *   3. REDIS ADAPTER (active/active cluster mode)
 *      — When REDIS_ENABLED=true, installs @socket.io/redis-adapter
 *      — Makes io.emit() and io.to(room).emit() transparently cross-instance
 *      — Uses two Redis connections (pub + sub) for Socket.IO message relay
 *      — Falls back to standalone mode with a warning if Redis is unavailable
 *
 *   4. HEARTBEAT (Redis presence refresh)
 *      — When Redis is enabled, a 10-second interval refreshes every
 *        locally-connected socket's entry in the Redis `users` hash
 *      — Keeps `updatedAt` current so other instances see the user as active
 *      — Prevents the stale-cleanup (in login.mjs) from removing live users
 *
 *   5. IO REGISTRY (circular dependency breaker)
 *      — Calls setIO(io) to register the Socket.IO server in the global
 *        ioRegistry.mjs module
 *      — This allows User.mjs to access io.emit() without importing server.mjs
 *        (which would trigger a second server to start)
 *
 * Configuration:    nodejs/config.mjs (environment variables)
 * User state:       nodejs/login.mjs  (local in-memory + optional Redis)
 * Event dispatch:   nodejs/websocketState.mjs
 *
 * Package:          h2invent/jitsi-admin-websocket
 * License:          AGPLv3
 */

import express from "express";
import bodyParser from "body-parser";
import http from "http";
import https from "https";
import fs from "fs";
import jwt from "jsonwebtoken";
import { Server } from "socket.io";

import { checkFileContains } from "./checkCertAndKey.js";
import { websocketState } from "./websocketState.mjs";
import { loginUser, getOnlineUser, getUserId } from "./login.mjs";
import { setIO } from "./ioRegistry.mjs";
import {
  MERCURE_INTERNAL_URL,
  PORT,
  WEBSOCKET_SECRET,
  KEY_FILE,
  CERT_FILE,
  REDIS_ENABLED,
  REDIS_HOST,
  REDIS_PORT
} from "./config.mjs";

const app = express();
const router = express.Router();
let server;

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SECTION 1 — HTTP SERVER                                                 ║
// ║                                                                          ║
// ║  Auto-detects TLS: if valid PEM cert + key files exist, starts HTTPS.    ║
// ║  Otherwise falls back to plain HTTP. The check scans for the ASCII       ║
// ║  magic strings "BEGIN CERTIFICATE" / "BEGIN PRIVATE KEY" in the files.   ║
// ║  Binary formats (DER, PKCS#7) will fail detection → HTTP fallback.       ║
// ║  Permission errors also trigger HTTP fallback with a German error msg.   ║
// ║  Port is configurable via the PORT env var (default 3000).               ║
// ╚══════════════════════════════════════════════════════════════════════════╝
try {
  if (checkFileContains(CERT_FILE, "BEGIN CERTIFICATE") && checkFileContains(KEY_FILE, "BEGIN PRIVATE KEY")) {
    console.log("✅ HTTPS Server wird gestartet.");
    server = https.createServer({
      key: fs.readFileSync(KEY_FILE),
      cert: fs.readFileSync(CERT_FILE)
    }, app);
  } else {
    console.log("⚠️ HTTPS Zertifikat nicht gefunden, HTTP Server wird gestartet.");
    server = http.createServer(app);
  }
} catch (err) {
  console.error("❌ HTTPS Setup fehlgeschlagen:", err);
  server = http.createServer(app);
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SECTION 2 — SOCKET.IO SERVER                                            ║
// ║                                                                          ║
// ║  Mounted at path `/ws`. Provides real-time bidirectional communication   ║
// ║  between browser clients and this server.                                ║
// ║                                                                          ║
// ║  AUTH MIDDLEWARE (lines 82-90 below):                                    ║
// ║    - Every connecting browser must present a JWT as query.token          ║
// ║    - JWT is verified against WEBSOCKET_SECRET (HS256)                    ║
// ║    - On success, socket.decoded is populated with the full JWT payload   ║
// ║    - On failure, connection is rejected with "Authentication error"      ║
// ║                                                                          ║
// ║  CONNECTION LIFECYCLE (lines 93-107 below):                              ║
// ║    - Joins the socket to rooms from JWT.rooms claim                      ║
// ║    - Calls loginUser() to register in local state + Redis                ║
// ║    - Emits sendUserStatus to the connecting client                       ║
// ║    - Emits sendOnlineUser (full user list) to ALL clients                ║
// ║    - Routes all client events to websocketState.mjs via socket.onAny()   ║
// ╚══════════════════════════════════════════════════════════════════════════╝
export const io = new Server(server, {
  path: "/ws",
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

// Section 5 — register in global registry (see ioRegistry.mjs)
setIO(io);

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SECTION 3 — REDIS ADAPTER (opt-in active/active cluster mode)           ║
// ║                                                                          ║
// ║  When REDIS_ENABLED="true", connects to Redis and installs the           ║
// ║  @socket.io/redis-adapter using two connections:                         ║
// ║    - pubClient: used to publish Socket.IO messages to other instances    ║
// ║    - subClient: duplicate, used to subscribe to messages from others     ║
// ║                                                                          ║
// ║  Once installed, io.emit() and io.to(room).emit() work transparently     ║
// ║  across all server instances that share the same Redis server.           ║
// ║                                                                          ║
// ║  If Redis is unreachable, the server logs a warning and continues in     ║
// ║  standalone mode — user presence is confined to the local instance.      ║
// ║                                                                          ║
// ║  The pubClient is also reused (as `redis`) for the heartbeat and by      ║
// ║  login.mjs for cross-instance user state.                                ║
// ╚══════════════════════════════════════════════════════════════════════════╝
let redis = null;

if (REDIS_ENABLED) {
  try {
    const { createAdapter } = await import("@socket.io/redis-adapter");
    const { createClient } = await import("redis");

    const pubClient = createClient({ url: `redis://${REDIS_HOST}:${REDIS_PORT}` });
    const subClient = pubClient.duplicate();

    await pubClient.connect();
    await subClient.connect();

    redis = pubClient;
    io.adapter(createAdapter(pubClient, subClient));
    console.log(`🔗 Redis-Adapter aktiviert (${REDIS_HOST}:${REDIS_PORT})`);
  } catch (err) {
    console.warn("⚠️ Redis-Adapter konnte nicht initialisiert werden, Standalone läuft:", err.message);
  }
} else {
  console.log("⚙️ Redis deaktiviert – Standalone-Modus");
}

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  Socket.IO Auth Middleware                                               ║                                                                          ║
// ║  Every browser WebSocket connection must pass JWT authentication.        ║
// ║  The JWT is passed as query parameter ?token=... in the connection URL.  ║
// ║  On success, socket.decoded receives the full JWT payload for            ║
// ║  downstream use.                                                         ║
// ╚══════════════════════════════════════════════════════════════════════════╝
io.use((socket, next) => {
  if (socket.handshake.query?.token) {
    jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET, (err, decoded) => {
      if (err) return next(new Error("Authentication error"));
      socket.decoded = decoded;
      next();
    });
  } else next(new Error("Authentication error"));
});

// ---- Socket.IO Connection Lifecycle ----
io.on("connection", async (socket) => {
  // Decode JWT again (no verification needed — middleware already verified it)
  // to extract the rooms array and join the socket to those rooms
  const jwtObj = jwt.decode(socket.handshake.query.token);
  if (jwtObj?.rooms) jwtObj.rooms.forEach(room => socket.join(room));

  // Register this socket in the user session manager (login.mjs)
  // This creates/updates a local User object AND syncs to Redis if enabled
  const user = await loginUser(socket);
  if (user) {
    // Initialize away timer for this user
    user.initUserAway?.();
    // Tell this client its own current status
    socket.emit("sendUserStatus", user.getStatus?.());
    // Tell this client its configured away timeout
    socket.emit("sendUserTimeAway", user.awayTime ?? 0);
    // Broadcast the updated online user list to ALL connected clients
    // (With Redis adapter, this reaches clients on all instances)
    io.emit("sendOnlineUser", JSON.stringify(await getOnlineUser()));
  }

  // Route disconnect events to websocketState.mjs
  socket.on("disconnect", () => websocketState("disconnect", socket, null));
  // Route ALL other client-emitted events to websocketState.mjs
  // This includes: setStatus, enterMeeting, leaveMeeting, stillOnline,
  //                getStatus, getMyStatus, openNewIframe, giveOnlineStatus,
  //                setAwayTime
  socket.onAny((event, data) => websocketState(event, socket, data));
});

// ---- Express Body Parsing (for Mercure POST) ----
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  HTTP ROUTES                                                             ║
// ║                                                                          ║
// ║  POST /.well-known/mercure  —  Mercure bridge: PHP → Node.js             ║
// ║    The PHP backend publishes messages here. Expects:                     ║
// ║      - Authorization: Bearer <JWT>  (verified against WEBSOCKET_SECRET)  ║
// ║      - Body: topic=<room>&data=<JSON>                                    ║
// ║    Calls io.to(topic).emit("mercure", data) to fan out to all sockets    ║
// ║    in that room. With Redis adapter, reaches clients on ALL instances.   ║
// ║                                                                          ║
// ║  GET  /.well-known/mercure  —  Liveness check (for Mercure clients)      ║
// ║  GET  /healthz             —  Health check (Docker HEALTHCHECK/curl)     ║
// ╚══════════════════════════════════════════════════════════════════════════╝
router.post(MERCURE_INTERNAL_URL, async (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.sendStatus(403);

  const token = authHeader.split(" ")[1];
  jwt.verify(token, WEBSOCKET_SECRET, async (err) => {
    if (err) return res.sendStatus(403);

    const data = req.body.data;
    const room = req.body.topic;
    // io.to(room).emit() fans out via Redis adapter to all instances
    io.to(room).emit("mercure", data);
    res.end("OK");
  });
});

router.get(MERCURE_INTERNAL_URL, (_, res) => res.sendStatus(200));
router.get("/healthz", (_, res) => res.sendStatus(200));
app.use("/", router);

// ---- Start server ----
server.listen(PORT, () => {
  console.log(`🚀 Server läuft auf Port ${PORT} (${REDIS_ENABLED ? "Cluster" : "Standalone"})`);
});

// ╔══════════════════════════════════════════════════════════════════════════╗
// ║  SECTION 4 — HEARTBEAT (Redis presence refresh)                          ║
// ║                                                                          ║
// ║  Runs every 10 seconds when REDIS_ENABLED=true and Redis connected.      ║
// ║  For each locally-connected socket, updates the user's entry in the      ║
// ║  Redis `users` hash.                                                     ║
// ║                                                                          ║
// ║  Purpose:                                                                ║
// ║    — Keeps updatedAt fresh so other instances see this user as active    ║
// ║    — Prevents login.mjs's stale-cleanup (2-min timeout) from removing    ║
// ║      the user if no explicit events fire within the window               ║
// ║    — Preserves existing Redis state (socketsCount, inMeetingCount,       ║
// ║      away flag) that was set by login.mjs event handlers                 ║
// ║                                                                          ║
// ║  The heartbeat does NOT create new users — it only refreshes entries     ║
// ║  that already exist in Redis (created by loginUser()).                   ║
// ╚══════════════════════════════════════════════════════════════════════════╝
if (REDIS_ENABLED && redis) {
  setInterval(async () => {
    try {
      const sockets = io.sockets.sockets;
      for (const socket of sockets.values()) {
        const userId = getUserId(socket);
        if (!userId) continue;

        // Read existing data to preserve counts set by login.mjs handlers
        const existing = await redis.hGet("users", userId);
        let existingData = {};
        try {
          existingData = existing ? JSON.parse(existing) : {};
        } catch {}

        const userData = {
          id: userId,
          status: existingData.status || (socket.decoded?.status === 1 ? "online" : "offline"),
          socketsCount: existingData.socketsCount || 1,
          inMeetingCount: existingData.inMeetingCount || 0,
          away: existingData.away || false,
          awayTime: existingData.awayTime || 5,
          updatedAt: Date.now()
        };

        await redis.hSet("users", userId, JSON.stringify(userData));
      }
    } catch (err) {
      console.error("Fehler beim Heartbeat:", err.message);
    }
  }, 10000);
}
