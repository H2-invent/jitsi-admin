import jwt from "jsonwebtoken";
import { User } from "./User.mjs";
import { WEBSOCKET_SECRET, REDIS_ENABLED, REDIS_HOST, REDIS_PORT } from "./config.mjs";

let redis = null;
let user = {}; // lokaler Fallback

// Redis verbinden, falls aktiviert
if (REDIS_ENABLED) {
  try {
    const { createClient } = await import("ioredis");
    redis = createClient({ host: REDIS_HOST, port: REDIS_PORT });
    await redis.connect();
    console.log("🔗 Redis-Client verbunden für globale User-Liste");
  } catch (err) {
    console.warn("⚠️ Redis nicht erreichbar – verwende lokalen User-State:", err.message);
    redis = null;
  }
}

export async function loginUser(socket) {
  if (jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET)) {
    const userId = getUserId(socket);
    if (!userId) return null;

    const initialStatus = getUserInitialOnlineStatus(socket);

    if (redis) {
      try {
        await redis.hset("users", userId, JSON.stringify({ id: userId, status: initialStatus, updatedAt: Date.now() }));
      } catch (err) {
        console.error(`[Redis] Fehler beim Speichern von User ${userId}:`, err.message);
      }
    } else {
      if (!user[userId]) user[userId] = new User(userId, socket, initialStatus);
      else user[userId].addSocket(socket);
    }

    return getUserFromSocket(socket);
  }
  return null;
}

export async function disconnectUser(socket) {
  const userId = getUserId(socket);
  leaveMeeting(socket);
  if (redis) {
    try {
      await redis.hdel("users", userId);
    } catch (err) {
      console.error(`[Redis] Fehler beim Löschen von User ${userId}:`, err.message);
    }
  } else if (user[userId]) {
    user[userId].removeSocket(socket);
  }
}

export async function getOnlineUSer() {
  if (redis) {
    try {
      const all = await redis.hgetall("users");
      const result = {};
      for (const [id, val] of Object.entries(all)) {
        try {
          const parsed = JSON.parse(val);
          const st = parsed.status || "online";
          if (!result[st]) result[st] = [];
          result[st].push(id);
        } catch {
          if (!result.online) result.online = [];
          result.online.push(id);
        }
      }
      return result;
    } catch (err) {
      console.error("[Redis] Fehler beim Abrufen der User-Liste:", err.message);
      return {};
    }
  } else {
    const tmpUser = {};
    for (const prop in user) {
      const u = user[prop];
      const tmpStatus = u.getStatus();
      if (!tmpUser[tmpStatus]) tmpUser[tmpStatus] = [];
      tmpUser[tmpStatus].push(u.getUserId());
    }
    return tmpUser;
  }
}

export function getUserFromSocket(socket) {
  const userId = getUserId(socket);
  return user[userId] || null;
}

function getUserId(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.sub;
}

function getUserInitialOnlineStatus(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.status === 1 ? "online" : "offline";
}
