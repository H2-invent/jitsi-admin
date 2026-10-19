import jwt from "jsonwebtoken";
import { User } from "./User.mjs";
import { WEBSOCKET_SECRET, REDIS_ENABLED, REDIS_HOST, REDIS_PORT, AWAY_TIME } from "./config.mjs";

let redis = null;
let user = {};

// Redis verbinden, falls aktiviert
if (REDIS_ENABLED) {
  try {
    const { createClient } = await import("redis");
    redis = createClient({ url: `redis://${REDIS_HOST}:${REDIS_PORT}` });
    await redis.connect();
    console.log("🔗 Redis-Client verbunden für globale User-Liste");
  } catch (err) {
    console.warn("⚠️ Redis nicht erreichbar – verwende lokalen User-State:", err.message);
    redis = null;
  }
}

// ── Redis helper functions ──────────────────────────────────────────
// Each helper is a no-op when Redis is disabled (redis === null).
// This eliminates the repeated `if (redis) { try { ... } catch { ... } }` pattern.

async function redisGetUser(userId) {
  if (!redis) return null;
  try {
    const raw = await redis.hGet("users", userId);
    return raw ? JSON.parse(raw) : null;
  } catch (err) {
    console.error(`[Redis] Fehler beim Lesen von User ${userId}:`, err.message);
    return null;
  }
}

async function redisSetUser(userId, data) {
  if (!redis) return;
  try {
    data.updatedAt = Date.now();
    await redis.hSet("users", userId, JSON.stringify(data));
  } catch (err) {
    console.error(`[Redis] Fehler beim Schreiben von User ${userId}:`, err.message);
  }
}

async function redisDeleteUser(userId) {
  if (!redis) return;
  try {
    await redis.hDel("users", userId);
  } catch (err) {
    console.error(`[Redis] Fehler beim Löschen von User ${userId}:`, err.message);
  }
}

async function redisGetAllUsers() {
  if (!redis) return {};
  try {
    return await redis.hGetAll("users");
  } catch (err) {
    console.error("[Redis] Fehler beim Abrufen aller User:", err.message);
    return {};
  }
}

async function redisUpdateField(userId, updater, createIfMissing = false) {
  if (!redis) return;
  try {
    const existing = await redis.hGet("users", userId);
    if (!existing && !createIfMissing) return;

    const data = existing ? JSON.parse(existing) : {
      id: userId,
      status: "online",
      socketsCount: 1,
      inMeetingCount: 0,
      away: false,
      awayTime: AWAY_TIME,
    };

    updater(data);
    data.updatedAt = Date.now();
    await redis.hSet("users", userId, JSON.stringify(data));
  } catch (err) {
    console.error(`[Redis] Fehler bei redisUpdateField für User ${userId}:`, err.message);
  }
}

export { redisGetUser, redisSetUser, redisGetAllUsers };

export async function loginUser(socket) {
  if (jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET)) {
    const userId = getUserId(socket);
    if (!userId) return null;

    const initialStatus = getUserInitialOnlineStatus(socket);

    // Always create/manage local User for socket operations (sendToAllSockets, away timer, etc.)
    if (!user[userId]) {
      user[userId] = new User(userId, socket, initialStatus);
    } else {
      user[userId].addSocket(socket);
    }

    // Also persist to Redis for cross-instance presence
    const existingData = await redisGetUser(userId);
    const socketCount = (existingData?.socketsCount || 0) + 1;
    await redisSetUser(userId, {
      id: userId,
      status: initialStatus,
      socketsCount: socketCount,
      inMeetingCount: existingData?.inMeetingCount || 0,
      away: existingData?.away || false,
      awayTime: existingData?.awayTime || AWAY_TIME
    });

    return getUserFromSocket(socket);
  }
  return null;
}

export async function disconnectUser(socket) {
  const userId = getUserId(socket);
  leaveMeeting(socket);

  // Always clean up local state
  if (user[userId]) {
    user[userId].removeSocket(socket);
  }

  // Sync to Redis
  const existing = await redisGetUser(userId);
  if (existing) {
    const newCount = Math.max((existing.socketsCount || 1) - 1, 0);
    if (newCount === 0) {
      await redisDeleteUser(userId);
    } else {
      existing.socketsCount = newCount;
      await redisSetUser(userId, existing);
    }
  }
}

export async function setStatus(socket, status) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    await user[userId].setStatus(status);
  }

  // Sync to Redis
  await redisUpdateField(userId, data => { data.status = status; data.away = false; });
}

export async function stillOnline(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].initUserAway();
  }

  // Sync to Redis
  await redisUpdateField(userId, data => { data.away = false; });
}

export async function enterMeeting(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].enterMeeting(socket);
  }

  // Sync to Redis
  await redisUpdateField(userId, data => { data.inMeetingCount = (data.inMeetingCount || 0) + 1; });
}

export async function leaveMeeting(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].leaveMeeting(socket);
  }

  // Sync to Redis
  await redisUpdateField(userId, data => { data.inMeetingCount = Math.max((data.inMeetingCount || 0) - 1, 0); });
}

export async function getUserStatus(socket) {
  const userId = getUserId(socket);
  // Prefer local state for immediate socket operations
  if (user[userId]) {
    return user[userId].getStatus();
  }
  // Fall back to Redis if available
  const data = await redisGetUser(userId);
  if (data) {
    return getUserStatusFromData(data);
  }
  return "offline";
}

export async function checkEmptySockets(socket) {
  const userId = getUserId(socket);
  // Check local state first
  if (user[userId]) {
    return user[userId].checkUserLeftTheApp();
  }
  // Fall back to Redis
  const data = await redisGetUser(userId);
  if (data) {
    return (data.socketsCount || 0) === 0;
  }
  return true;
}

export async function setAwayTime(socket, awayTime) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    await user[userId].setAwayTime(awayTime);
  }

  // Sync to Redis
  await redisUpdateField(userId, data => { data.awayTime = parseInt(awayTime) || AWAY_TIME; });
}

export async function getStatusForListOfIds(socket, list) {
  const ids = JSON.parse(list);
  const res = {};
  const allRedisUsers = await redisGetAllUsers();
  if (Object.keys(allRedisUsers).length > 0) {
    for (const id of ids) {
      if (allRedisUsers[id]) {
        try {
          res[id] = getUserStatusFromData(JSON.parse(allRedisUsers[id]));
        } catch {
          res[id] = "offline";
        }
      } else {
        res[id] = "offline";
      }
    }
  } else {
    for (const id of ids) {
      try {
        const tmpUser = user[id];
        if (tmpUser) {
          res[id] = tmpUser.getStatus();
        } else {
          res[id] = "offline";
        }
      } catch (e) {
        res[id] = "offline";
      }
    }
  }
  socket.emit("giveOnlineStatus", JSON.stringify(res));
}

export async function getOnlineUser() {
  const all = await redisGetAllUsers();
  if (Object.keys(all).length > 0) {
    const result = {};
    for (const [id, val] of Object.entries(all)) {
      try {
        const parsed = JSON.parse(val);
        const st = getUserStatusFromData(parsed);
        if (!result[st]) result[st] = [];
        result[st].push(id);
      } catch {
        if (!result.online) result.online = [];
        result.online.push(id);
      }
    }
    return result;
  } else {
    const tmpUser = {};
    for (const prop in user) {
      const u = user[prop];
      const tmpStatus = u.getStatus();
      if (!tmpUser[tmpStatus]) tmpUser[tmpStatus] = [];
      tmpUser[tmpStatus].push(u.userId);
    }
    return tmpUser;
  }
}

function getUserStatusFromData(data) {
  if ((data.socketsCount || 0) === 0) return "offline";
  if ((data.inMeetingCount || 0) > 0) return "inMeeting";
  if (data.away) return "away";
  return data.status || "online";
}

export function getUserFromSocket(socket) {
  const userId = getUserId(socket);
  return user[userId] || null;
}

export function getUserId(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.sub;
}

export function getUserInitialOnlineStatus(socket) {
  const jwtObj = jwt.decode(socket.handshake.query.token);
  return jwtObj?.status === 1 ? "online" : "offline";
}

// Stale Users aus Redis entfernen (alle 60 Sekunden)
if (REDIS_ENABLED && redis) {
  setInterval(async () => {
    try {
      const all = await redisGetAllUsers();
      if (Object.keys(all).length === 0) return;
      const now = Date.now();
      const TIMEOUT = 120000;
      for (const [id, val] of Object.entries(all)) {
        try {
          const data = JSON.parse(val);
          if (now - data.updatedAt > TIMEOUT) {
            await redisDeleteUser(id);
            console.log(`🕐 Stale User ${id} aus Redis entfernt (letztes Update vor ${Math.round((now - data.updatedAt) / 1000)}s)`);
          }
        } catch { /* skip malformed entries */ }
      }
    } catch (err) {
      console.error("[Redis] Fehler beim Cleanup stale Users:", err.message);
    }
  }, 60000);
}
