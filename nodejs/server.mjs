import express from "express";
import bodyParser from "body-parser";
import http from "http";
import https from "https";
import fs from "fs";
import jwt from "jsonwebtoken";
import { Server } from "socket.io";

import { checkFileContains } from "./checkCertAndKey.js";
import {
  getOnlineUSer,
  loginUser
} from "./login.mjs";
import { websocketState } from "./websocketState.mjs";
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

try {
  if (
    checkFileContains(CERT_FILE, "BEGIN CERTIFICATE") &&
    checkFileContains(KEY_FILE, "BEGIN PRIVATE KEY")
  ) {
    console.log("✅ Zertifikat & Key gefunden – HTTPS Server wird gestartet.");
    server = https.createServer(
      {
        key: fs.readFileSync(KEY_FILE),
        cert: fs.readFileSync(CERT_FILE)
      },
      app
    );
  } else {
    console.log("⚠️ Zertifikat ungültig oder nicht gefunden – HTTP Server wird gestartet.");
    server = http.createServer(app);
  }
} catch (err) {
  console.error("❌ HTTPS Setup fehlgeschlagen:", err);
  server = http.createServer(app);
}

// 🧠 Socket.IO Server-Instanz
export const io = new Server(server, {
  path: "/ws",
  cors: {
    origin: "*",
    methods: ["GET", "POST"]
  }
});

if (REDIS_ENABLED) {
  try {
    const { createAdapter } = await import("@socket.io/redis-adapter");
    const { createClient } = await import("redis");

    const pubClient = createClient({ url: `redis://${REDIS_HOST}:${REDIS_PORT}` });
    const subClient = pubClient.duplicate();

    await pubClient.connect();
    await subClient.connect();

    io.adapter(createAdapter(pubClient, subClient));
    console.log(`🔗 [Socket.IO] Redis-Adapter aktiviert (${REDIS_HOST}:${REDIS_PORT})`);
  } catch (err) {
    console.warn("⚠️ [Socket.IO] Redis-Adapter konnte nicht initialisiert werden – Fallback auf Standalone:", err.message);
  }
} else {
  console.log("⚙️ [Socket.IO] Redis deaktiviert – läuft im Single-Node-Modus.");
}

// 🧱 Authentifizierung über JWT
io.use(function (socket, next) {
  if (socket.handshake.query && socket.handshake.query.token) {
    jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET, function (err, decoded) {
      if (err) {
        console.log("❌ Ungültiges JWT-Secret");
        return next(new Error("Authentication error"));
      }
      socket.decoded = decoded;
      next();
    });
  } else {
    next(new Error("Authentication error"));
  }
});

io.on("connection", async (socket) => {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  if (!jwtObj || !jwtObj.rooms) return;

  for (const room of j
