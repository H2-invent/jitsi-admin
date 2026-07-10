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
    if (redis) {
      try {
        const existing = await redis.hGet("users", userId);
        const existingData = existing ? JSON.parse(existing) : null;
        const socketCount = (existingData?.socketsCount || 0) + 1;
        await redis.hSet("users", userId, JSON.stringify({
          id: userId,
          status: initialStatus,
          socketsCount: socketCount,
          inMeetingCount: existingData?.inMeetingCount || 0,
          away: existingData?.away || false,
          awayTime: existingData?.awayTime || AWAY_TIME,
          updatedAt: Date.now()
        }));
      } catch (err) {
        console.error(`[Redis] Fehler beim Speichern von User ${userId}:`, err.message);
      }
    }

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
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        const newCount = Math.max((data.socketsCount || 1) - 1, 0);
        if (newCount === 0) {
          await redis.hDel("users", userId);
        } else {
          data.socketsCount = newCount;
          data.updatedAt = Date.now();
          await redis.hSet("users", userId, JSON.stringify(data));
        }
      }
    } catch (err) {
      console.error(`[Redis] Fehler beim Löschen von User ${userId}:`, err.message);
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
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        data.status = status;
        data.away = false;
        data.updatedAt = Date.now();
        await redis.hSet("users", userId, JSON.stringify(data));
      }
    } catch (err) {
      console.error(`[Redis] Fehler beim Setzen des Status von User ${userId}:`, err.message);
    }
  }
}

export async function stillOnline(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].initUserAway();
  }

  // Sync to Redis
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        data.away = false;
        data.updatedAt = Date.now();
        await redis.hSet("users", userId, JSON.stringify(data));
      }
    } catch (err) {
      console.error(`[Redis] Fehler bei stillOnline von User ${userId}:`, err.message);
    }
  }
}

export async function enterMeeting(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].enterMeeting(socket);
  }

  // Sync to Redis
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        data.inMeetingCount = (data.inMeetingCount || 0) + 1;
        data.updatedAt = Date.now();
        await redis.hSet("users", userId, JSON.stringify(data));
      }
    } catch (err) {
      console.error(`[Redis] Fehler bei enterMeeting von User ${userId}:`, err.message);
    }
  }
}

export async function leaveMeeting(socket) {
  const userId = getUserId(socket);
  // Always update local state
  if (user[userId]) {
    user[userId].leaveMeeting(socket);
  }

  // Sync to Redis
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        data.inMeetingCount = Math.max((data.inMeetingCount || 0) - 1, 0);
        data.updatedAt = Date.now();
        await redis.hSet("users", userId, JSON.stringify(data));
      }
    } catch (err) {
      console.error(`[Redis] Fehler bei leaveMeeting von User ${userId}:`, err.message);
    }
  }
}

export async function getUserStatus(socket) {
  const userId = getUserId(socket);
  // Prefer local state for immediate socket operations
  if (user[userId]) {
    return user[userId].getStatus();
  }
  // Fall back to Redis if available
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        if ((data.socketsCount || 0) === 0) return "offline";
        if ((data.inMeetingCount || 0) > 0) return "inMeeting";
        if (data.away) return "away";
        return data.status || "online";
      }
    } catch (err) {
      console.error(`[Redis] Fehler bei getUserStatus von User ${userId}:`, err.message);
    }
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
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        return (data.socketsCount || 0) === 0;
      }
      return true;
    } catch (err) {
      console.error(`[Redis] Fehler bei checkEmptySockets von User ${userId}:`, err.message);
    }
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
  if (redis) {
    try {
      const existing = await redis.hGet("users", userId);
      if (existing) {
        const data = JSON.parse(existing);
        data.awayTime = parseInt(awayTime) || AWAY_TIME;
        data.updatedAt = Date.now();
        await redis.hSet("users", userId, JSON.stringify(data));
      }
    } catch (err) {
      console.error(`[Redis] Fehler bei setAwayTime von User ${userId}:`, err.message);
    }
  }
}

export async function getStatusForListOfIds(socket, list) {
  const ids = JSON.parse(list);
  const res = {};
  if (redis) {
    try {
      for (const id of ids) {
        const existing = await redis.hGet("users", id);
        if (existing) {
          const data = JSON.parse(existing);
          if ((data.socketsCount || 0) === 0) {
            res[id] = "offline";
          } else if ((data.inMeetingCount || 0) > 0) {
            res[id] = "inMeeting";
          } else if (data.away) {
            res[id] = "away";
          } else {
            res[id] = data.status || "online";
          }
        } else {
          res[id] = "offline";
        }
      }
    } catch (err) {
      console.error("[Redis] Fehler bei getStatusForListOfIds:", err.message);
      for (const id of ids) res[id] = "offline";
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

export async function getOnlineUSer() {
  if (redis) {
    try {
      const all = await redis.hGetAll("users");
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
      const all = await redis.hGetAll("users");
      const now = Date.now();
      const TIMEOUT = 120000;
      for (const [id, val] of Object.entries(all)) {
        try {
          const data = JSON.parse(val);
          if (now - data.updatedAt > TIMEOUT) {
            await redis.hDel("users", id);
            console.log(`🕐 Stale User ${id} aus Redis entfernt (letztes Update vor ${Math.round((now - data.updatedAt) / 1000)}s)`);
          }
        } catch { /* skip malformed entries */ }
      }
    } catch (err) {
      console.error("[Redis] Fehler beim Cleanup stale Users:", err.message);
    }
  }, 60000);
}
