import express from "express";
import bodyParser from "body-parser";
import http from "http";
import https from "https";
import fs from "fs";
import jwt from "jsonwebtoken";
import { Server } from "socket.io";

import { checkFileContains } from "./checkCertAndKey.js";
import { websocketState } from "./websocketState.mjs";
import { loginUser, getOnlineUSer, getUserId } from "./login.mjs";
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

// HTTP oder HTTPS
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

// Socket.IO Server
export const io = new Server(server, {
  path: "/ws",
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

let redis = null;

// Optional: Redis Adapter für Cluster
if (REDIS_ENABLED) {
  try {
    const { createAdapter } = await import("@socket.io/redis-adapter");
    const { createClient } = await import("redis");

    const pubClient = createClient({ url: `redis://${REDIS_HOST}:${REDIS_PORT}` });
    const subClient = pubClient.duplicate();

    await pubClient.connect();
    await subClient.connect();

    redis = pubClient; // global für Heartbeat
    io.adapter(createAdapter(pubClient, subClient));
    console.log(`🔗 Redis-Adapter aktiviert (${REDIS_HOST}:${REDIS_PORT})`);
  } catch (err) {
    console.warn("⚠️ Redis-Adapter konnte nicht initialisiert werden, Standalone läuft:", err.message);
  }
} else {
  console.log("⚙️ Redis deaktiviert – Standalone-Modus");
}

// JWT Auth
io.use((socket, next) => {
  if (socket.handshake.query?.token) {
    jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET, (err, decoded) => {
      if (err) return next(new Error("Authentication error"));
      socket.decoded = decoded;
      next();
    });
  } else next(new Error("Authentication error"));
});

// Socket.IO Events
io.on("connection", async (socket) => {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  if (jwtObj?.rooms) jwtObj.rooms.forEach(room => socket.join(room));

  const user = await loginUser(socket);
  if (user) {
    user.initUserAway?.();
    socket.emit("sendUserStatus", user.getStatus?.());
    socket.emit("sendUserTimeAway", user.awayTime ?? 0);
    io.emit("sendOnlineUser", JSON.stringify(await getOnlineUSer()));
  }

  socket.on("disconnect", () => websocketState("disconnect", socket, null));
  socket.onAny((event, data) => websocketState(event, socket, data));
});

// Express Middleware
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());

// MERCURE Webhook
router.post(MERCURE_INTERNAL_URL, async (req, res) => {
  const authHeader = req.headers.authorization;
  if (!authHeader) return res.sendStatus(403);

  const token = authHeader.split(" ")[1];
  jwt.verify(token, WEBSOCKET_SECRET, async (err) => {
    if (err) return res.sendStatus(403);

    const data = req.body.data;
    const room = req.body.topic;
    io.to(room).emit("mercure", data);
    res.end("OK");
  });
});

router.get(MERCURE_INTERNAL_URL, (_, res) => res.sendStatus(200));
router.get("/healthz", (_, res) => res.sendStatus(200));
app.use("/", router);

// Server starten
server.listen(PORT, () => {
  console.log(`🚀 Server läuft auf Port ${PORT} (${REDIS_ENABLED ? "Cluster" : "Standalone"})`);
});

// 🔄 Heartbeat: lokale User alle 10 Sekunden erneut in Redis registrieren
if (REDIS_ENABLED && redis) {
  setInterval(async () => {
    try {
      const usersFromRedis = {};

      // Alle lokal verbundenen Sockets in Redis registrieren (Heartbeat)
      for (const socket of io.sockets.sockets.values()) {
        const userId = getUserId(socket);
        if (!userId) continue;

        const userData = {
          id: userId,
          status: socket.decoded?.status === 1 ? 'online' : 'offline',
          updatedAt: Date.now()
        };

        await redis.hset("users", userId, JSON.stringify(userData));

        // Für Testausgabe vorbereiten
        const status = userData.status;
        if (!usersFromRedis[status]) usersFromRedis[status] = [];
        usersFromRedis[status].push(userId);
      }

      // 🌐 Test: globale Userliste ausgeben
      console.log("🌐 Globale Userliste (Redis + Heartbeat):", usersFromRedis);

    } catch (err) {
      console.error("Fehler beim Heartbeat / globale Userliste:", err.message);
    }
  }, 10000); // alle 10 Sekunden
}

