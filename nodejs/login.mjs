import jwt from "jsonwebtoken";
import { User } from "./User.mjs";
import { WEBSOCKET_SECRET, REDIS_HOST, REDIS_PORT, REDIS_ENABLED } from "./config.mjs";

let redis = null;
let user = {}; // Lokales Fallback

// 🧠 Versuche Redis zu verbinden, wenn konfiguriert
if (REDIS_ENABLED) {
  try {
    const { createClient } = await import("ioredis");
    redis = createClient({
      host: REDIS_HOST,
      port: REDIS_PORT,
    });
    await redis.connect();
    console.log("🔗 [login.mjs] Redis-Client verbunden für globale User-Verwaltung");
  } catch (err) {
    console.warn("⚠️ [login.mjs] Konnte Redis nicht verbinden, verwende lokalen User-State:", err.message);
    redis = null;
  }
} else {
  console.log("⚙️ [login.mjs] Redis deaktiviert – verwende lokalen User-State.");
}

/**
 * Benutzer beim Login registrieren
 */
export async function loginUser(socket) {
  if (jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET)) {
    const userId = getUserId(socket);
    if (!userId) return null;

    console.log("👤 Login:", userId);

    if (redis) {
      // 🧩 Benutzer in Redis registrieren
      const initialStatus = getUserInitialOnlineStatus(socket);
      const userData = JSON.stringify({
        id: userId,
        status: initialStatus,
        updatedAt: Date.now(),
      });
      await redis.hset("users", userId, userData);
    } else {
      // 🔁 Lokales Verhalten
      if (typeof user[userId] === "undefined") {
        user[userId] = new User(userId, socket, getUserInitialOnlineStatus(socket));
      } else {
        user[userId].addSocket(socket);
      }
    }
    return getUserFromSocket(socket);
  }
  return null;
}

/**
 * Benutzer beim Disconnect abmelden
 */
export async function disconnectUser(socket) {
  const userId = getUserId(socket);
  leaveMeeting(socket);
  if (redis) {
    await redis.hdel("users", userId);
  } else if (user[userId]) {
    user[userId].removeSocket(socket);
  }
}

/**
 * Online-User prüfen
 */
export async function getOnlineUSer() {
  if (redis) {
    const all = await redis.hgetall("users");
    const result = { online: [], offline: [], away: [], busy: [] };

    Object.entries(all).forEach(([id, data]) => {
      try {
        const parsed = JSON.parse(data);
        const st = parsed.status || "online";
        if (!result[st]) result[st] = [];
        result[st].push(id);
      } catch {
        result.online.push(id);
      }
    });
    return result;
  } else {
    const tmpUser = {};
    for (const prop in user) {
      const u = user[prop];
      const tmpStatus = u.getStatus();
      if (typeof tmpUser[tmpStatus] === "undefined") {
        tmpUser[tmpStatus] = [];
      }
      tmpUser[tmpStatus].push(u.getUserId());
    }
    return tmpUser;
  }
}

/**
 * Benutzerstatus setzen
 */
export async function setStatus(socket, status) {
  const userId = getUserId(socket);
  if (redis) {
    const u = await redis.hget("users", userId);
    if (u) {
      const parsed = JSON.parse(u);
      parsed.status = status;
      parsed.updatedAt = Date.now();
      await redis.hset("users", userId, JSON.stringify(parsed));
    }
  } else if (user[userId]) {
    user[userId].setStatus(status);
  }
}

/**
 * Status von IDs abrufen
 */
export async function getStatusForListOfIds(socket, list) {
  const ids = JSON.parse(list);
  const res = {};

  if (redis) {
    const all = await redis.hmget("users", ...ids);
    ids.forEach((id, idx) => {
      if (all[idx]) {
        const parsed = JSON.parse(all[idx]);
        res[id] = parsed.status || "offline";
      } else {
        res[id] = "offline";
      }
    });
  } else {
    for (const l of ids) {
      const tmpUser = user[l];
      res[l] = tmpUser ? tmpUser.getStatus() : "offline";
    }
  }
  socket.emit("giveOnlineStatus", JSON.stringify(res));
}

/**
 * Hilfsfunktionen
 */
export function getUserFromSocket(socket) {
  const userId = getUserId(socket);
  return user[userId] ? user[userId] : null;
}

function getUserId(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.sub;
}

function getUserInitialOnlineStatus(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.status === 1 ? "online" : "offline";
}

// Optional: Fallback-Funktionen
export function stillOnline(socket) {
  if (!redis && user[getUserId(socket)]) {
    user[getUserId(socket)].initUserAway();
  }
}
export function enterMeeting(socket) {
  if (!redis && user[getUserId(socket)]) {
    user[getUserId(socket)].enterMeeting(socket);
  }
}
export function leaveMeeting(socket) {
  if (!redis && user[getUserId(socket)]) {
    user[getUserId(socket)].leaveMeeting(socket);
  }
}
export function checkEmptySockets(socket) {
  if (!redis) {
    try {
      return user[getUserId(socket)].checkUserLeftTheApp();
    } catch {
      return false;
    }
  }
  return false;
}
export function setAwayTime(socket, awayTime) {
  if (!redis) {
    const u = getUserFromSocket(socket);
    if (u) u.setAwayTime(awayTime);
  }
}
