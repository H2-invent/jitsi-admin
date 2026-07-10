# Redis in the jitsi-admin WebSocket System

This document describes how Redis is used in the jitsi-admin WebSocket infrastructure
for active/active clustering. It covers the data model, write/read paths, cleanup
mechanisms, and operational characteristics.

## Table of Contents

- [Overview](#overview)
- [Data Stored in Redis](#data-stored-in-redis)
  - [1. Socket.IO Internal Data (Adapter)](#1-socketio-internal-data-adapter)
  - [2. User Presence Hash (users key)](#2-user-presence-hash-users-key)
- [How the Data Is Used](#how-the-data-is-used)
  - [Read Paths](#read-paths)
  - [Write Paths](#write-paths)
  - [The Dual-State Pattern](#the-dual-state-pattern)
  - [Cross-Instance Effect](#cross-instance-effect)
- [Data Cleanup](#data-cleanup)
  - [A. Explicit Cleanup on Disconnect](#a-explicit-cleanup-on-disconnect)
  - [B. Stale Cleanup Interval](#b-stale-cleanup-interval)
  - [C. Heartbeat — Prevent Cleanup of Live Users](#c-heartbeat--prevent-cleanup-of-live-users)
  - [Lifecycle Summary](#lifecycle-summary)
- [Other Relevant Details](#other-relevant-details)
  - [Three Separate Redis Connections](#three-separate-redis-connections)
  - [Single Hash — Not Multiple Keys](#single-hash--not-multiple-keys)
  - [Graceful Fallback](#graceful-fallback)
  - [Room Membership vs User Presence](#room-membership-vs-user-presence)
  - [Redis Is Optional](#redis-is-optional)
- [Redis Interaction Reference](#redis-interaction-reference)
- [How to Inspect Redis at Runtime](#how-to-inspect-redis-at-runtime)

---

## Overview

Redis serves **two distinct purposes** in the WebSocket system:

| Purpose | Mechanism | Managed By |
|---------|-----------|------------|
| **Socket.IO message relay** between instances | `@socket.io/redis-adapter` (Pub/Sub channels) | Adapter internals — invisible to application code |
| **User presence map** shared across instances | Redis hash key `"users"` | `login.mjs` (reads/writes) + `WebsocketServer.mjs` (heartbeat writes) + stale cleanup (login.mjs) |

These are independent concerns. The adapter handles cross-instance delivery of
`io.emit()` and `io.to(room).emit()`. The user presence hash handles cross-instance
queries for online status, away detection, and meeting state.

Redis is **completely optional**. When `REDIS_ENABLED` is not `"true"`, the system
runs in standalone mode using only local in-memory state.

---

## Data Stored in Redis

### 1. Socket.IO Internal Data (Adapter)

The `@socket.io/redis-adapter` uses Redis Pub/Sub channels internally to relay
Socket.IO messages between instances. This data includes:

- Socket.IO event payloads (the data passed to `io.emit()` and `io.to().emit()`)
- Room membership metadata (which socket joined which room)
- Connection state for cross-instance coordination

These keys are an implementation detail of the adapter and are **never accessed
directly** by application code. The adapter creates its own key namespace prefixed
with `socket.io`. You can observe them via `redis-cli KEYS *` but should never
modify them.

### 2. User Presence Hash (`users` key)

A single Redis hash called `users` where each **field** is a user ID and each
**value** is a JSON string:

```
Redis key:   users  (type: hash)
Field:       <userId>  (e.g. "abc-123-def-456")
Value:       JSON object  (see schema below)
```

**Schema of each user entry:**

```json
{
  "id": "abc-123-def-456",
  "status": "online",
  "socketsCount": 2,
  "inMeetingCount": 1,
  "away": false,
  "awayTime": 5,
  "updatedAt": 1710435600000
}
```

| Field | Type | Meaning | Source |
|-------|------|---------|--------|
| `id` | string | User UID — from JWT `sub` claim, maps to `User.uid` in the PHP entity | `loginUser()` — extracted from JWT |
| `status` | string | User's explicit status: `"online"`, `"offline"`, `"away"`, `"busy"`, or any custom string set by the client | `loginUser()` (initial), `setStatus()` (client-triggered) |
| `socketsCount` | integer | Total browser tabs connected for this user **across all instances** | `loginUser()` (+1), `disconnectUser()` (−1) |
| `inMeetingCount` | integer | Number of those tabs currently in a Jitsi meeting | `enterMeeting()` (+1), `leaveMeeting()` (−1) |
| `away` | boolean | Whether the server-side away timer has fired. Separate from a user manually setting `status: "away"` | `initUserAway()` in `User.mjs` |
| `awayTime` | integer | User's configured away timeout in minutes (default: 5, from `AWAY_TIME` env var) | `setAwayTime()` from client, persisted from JWT `status` field |
| `updatedAt` | integer | Unix timestamp in milliseconds of the last write to this entry | Every write function + heartbeat |

**This is the only application-level key written to Redis.** No other keys are
created, modified, or read by the application code.

---

## How the Data Is Used

Redis serves as the **shared truth** between multiple WebSocket server instances.
Each instance maintains its own local in-memory `user` object (holding native
Socket.IO socket handles for direct `socket.emit()` calls), but the Redis hash
is what enables cross-instance presence queries.

### Read Paths

| Function | File:Line | Redis Call | Reads | Triggered When |
|----------|-----------|------------|-------|----------------|
| `getOnlineUSer()` | `login.mjs:295-326` | `hGetAll("users")` | Full user hash | Browser connects, `getStatus` event, any status change, meeting enter/leave — called by `io.emit("sendOnlineUser")` |
| `getUserStatus()` | `login.mjs:183-204` | `hGet("users", userId)` | Single user | When the local instance doesn't have the user in memory but needs their status (fallback only) |
| `checkEmptySockets()` | `login.mjs:207-227` | `hGet("users", userId)` | Single user | 7 seconds after disconnect, to check if all tabs are closed |
| `getStatusForListOfIds()` | `login.mjs:252-293` | `hGet("users", id)` loop | Multiple users | Browser requests status for specific user IDs via `giveOnlineStatus` event |
| Heartbeat | `WebsocketServer.mjs:279-308` | `hGet("users", userId)` | Single user (read-modify-write) | Every 10 seconds for each locally-connected socket |

### Write Paths

| Function | File:Line | Redis Call | What It Writes | Trigger |
|----------|-----------|------------|----------------|---------|
| `loginUser()` | `login.mjs:36-49` | `hSet("users", userId, ...)` | New or updated user entry: `id`, `status`, `socketsCount` (+1), `inMeetingCount` (preserved), `away`, `awayTime`, `updatedAt` | Browser connects (Socket.IO `connection` event) |
| `disconnectUser()` | `login.mjs:70-84` | `hSet(...)` or `hDel(...)` | Decrements `socketsCount`. If drops to 0: deletes entry via `hDel`. Otherwise: updates count and `updatedAt` | Browser tab closes or connection drops |
| `setStatus()` | `login.mjs:98-107` | `hSet("users", userId, ...)` | Updates `status` field, resets `away = false`, sets `updatedAt` | User clicks status change in the UI (online/away/busy/offline dropdown) |
| `stillOnline()` | `login.mjs:122-130` | `hSet("users", userId, ...)` | Sets `away = false`, updates `updatedAt` | Browser tab becomes visible (every 2s via polling + Page Visibility API) |
| `enterMeeting()` | `login.mjs:145-152` | `hSet("users", userId, ...)` | Increments `inMeetingCount` (+1), updates `updatedAt` | User joins a Jitsi meeting |
| `leaveMeeting()` | `login.mjs:168-176` | `hSet("users", userId, ...)` | Decrements `inMeetingCount` (−1, min 0), updates `updatedAt` | User leaves a Jitsi meeting |
| `setAwayTime()` | `login.mjs:238-245` | `hSet("users", userId, ...)` | Updates `awayTime`, `updatedAt` | User changes the away timeout value in the UI |
| Heartbeat | `WebsocketServer.mjs:279-308` | `hSet("users", userId, ...)` | Refreshes entire entry (preserves existing counts written by `login.mjs`), updates `updatedAt` | Every 10s for each socket on this instance |

### The Dual-State Pattern

Every write function in `login.mjs` follows the same pattern — update local memory
**first**, then sync to Redis **second**:

```
write function(socket, data):
  ├─ Update local user[userId]  ← always, mandatory
  └─ if (redis): sync to Redis  ← optional, for cross-instance
```

The local `user` object holds **actual Socket.IO socket references** — native TCP
handles that cannot be serialized. These are needed for `sendToAllSockets()` which
directly emits events to a specific user's browser tabs.

Redis carries the **abstract presence state** — status, counts, away flag. Other
instances read this to know who is online without needing direct socket access.

### Cross-Instance Effect

Here is the concrete flow that demonstrates why Redis is necessary:

```
1. User X connects to Instance A
   → loginUser() on A:
      - Creates local User X (with socket handle)
      - Writes to Redis: HSET users "user-x" '{"socketsCount":1,...}'

2. User Y connects to Instance B
   → getOnlineUSer() on B:
      - Reads Redis: HGETALL users
      - Finds: {"user-x": {"socketsCount":1,...}}
      → io.emit("sendOnlineUser", {online:["user-x"]})

3. User Y's browser sees User X as "online" — even though
   X is on a completely different instance.
```

Without Redis, Instance B would only know about users connected to Instance B.
The `users` hash is the communication channel that makes all instances converge
on the same view of "who is online."

---

## Data Cleanup

There are **two cleanup mechanisms**, one explicit and one time-based. A heartbeat
prevents live users from being incorrectly removed by the time-based cleanup.

### A. Explicit Cleanup on Disconnect

**File:** `login.mjs:60-88` — `disconnectUser()`

When a browser tab closes (or the Socket.IO connection drops):

1. `leaveMeeting(socket)` is called to decrement `inMeetingCount` in Redis
2. The socket is removed from the local `user[userId]` object
3. Redis is read: `HGET users <userId>` to get the current `socketsCount`
4. `socketsCount` is decremented by 1
5. **If `socketsCount === 0`**: the entry is **deleted** from Redis via `HDEL users <userId>`
6. **If `socketsCount > 0`** (user still has other tabs open): only the count is updated, entry stays

This is the **primary** cleanup path. The user is removed from Redis as soon as
the last tab disconnects. Multi-tab users are handled correctly — only the counter
decrements, the entry persists.

### B. Stale Cleanup Interval

**File:** `login.mjs:350-369`

A 60-second interval scans the entire `users` hash and removes entries whose
`updatedAt` timestamp is older than 2 minutes:

```
Every 60 seconds:
  all_users = HGETALL users
  for each user in all_users:
    if (Date.now() - user.updatedAt) > 120000ms:
      HDEL users user.id
      log: "Stale User <id> aus Redis entfernt"
```

This handles **orphaned entries** — when a server instance crashes, its sockets
are gone, but `disconnectUser()` was never called for any of them. The heartbeat
(see below) normally keeps `updatedAt` current for live users. If no heartbeat
has refreshed a user's entry for 2 minutes, the user is presumed disconnected
and removed.

### C. Heartbeat — Prevents Cleanup of Live Users

**File:** `WebsocketServer.mjs:279-308`

Every 10 seconds, for every socket connected to the local instance:

1. Reads the existing Redis entry for the user (`HGET users <userId>`)
2. Extracts current counts (`socketsCount`, `inMeetingCount`) — preserving values
   that were set by `login.mjs` event handlers
3. Writes back the full entry with `updatedAt: Date.now()`

This means that as long as the instance is alive and the user remains connected,
`updatedAt` is never more than 10 seconds old. The stale cleanup's 2-minute
threshold is never reached for live users.

### Lifecycle Summary

```
User connects     → Redis entry created            (updatedAt = now, socketsCount = 1)
Heartbeat         → updatedAt refreshed every 10s  (prevents stale cleanup)
User opens 2nd tab→ socketsCount incremented to 2  (Redis entry stays)
User closes 1 tab → socketsCount decremented to 1  (Redis entry stays)
User closes last tab→ socketsCount hits 0 → HDEL   (entry removed immediately)
Instance crashes  → heartbeat stops                (updatedAt stops updating)
                  → after 2 minutes: stale cleanup removes entry
User idle (away)  → entry stays, away field updated to true
```

---

## Other Relevant Details

### Three Separate Redis Connections

The system opens **three** TCP connections to the same Redis server:

| Connection | Created In | Used For |
|-----------|-----------|----------|
| `pubClient` | `WebsocketServer.mjs` line 156 | Publishes Socket.IO messages to other instances via the adapter. Also reused as the heartbeat writer and passed to `login.mjs` via the module-level `redis` variable |
| `subClient` | `pubClient.duplicate()` at line 157 | Subscribes to Socket.IO messages from other instances — internal to the adapter |
| `login.mjs` client | `login.mjs` lines 11-12 | **Separate connection** created at module load time for user presence KV operations (`hGet`/`hSet`/`hGetAll`/`hDel`) |

The `pubClient` and the `login.mjs` client are **two independent connections**.
This is important because they serve different purposes (adapter Pub/Sub vs
application KV storage) and have different error handling. The adapter requires
exactly two connections (pub + sub) for its internal operation; the application
KV operations work with a single connection.

### Single Hash — Not Multiple Keys

All user presence data goes into a **single Redis hash** named `users`. This means:

| Command | Returns | Performance |
|---------|---------|-------------|
| `HLEN users` | Total user count across all instances | O(1) |
| `HGET users <uid>` | Single user's data | O(1) |
| `HGETALL users` | Complete user map in one call | O(N) where N = number of connected users |

**No key expiry (TTL)** is set on the hash or its fields. Cleanup is entirely
explicit (disconnect) or interval-driven (stale sweep). There is no automatic
deletion via Redis built-in mechanisms.

**Scale considerations:** `HGETALL users` is called every time `getOnlineUSer()`
runs (on every connect, status change, meeting enter/leave). For deployments
with thousands of concurrent users, this could become a hot path. In practice,
jitsi-admin deployments have user counts in the hundreds to low thousands, where
the O(N) cost is negligible.

### Graceful Fallback

If Redis is unreachable at startup:

1. `WebsocketServer.mjs` (lines 162-164): The adapter initialization fails. The
   `io.adapter()` call is never made. The server logs a warning in German:
   `"Redis-Adapter konnte nicht initialisiert werden, Standalone läuft:"` and
   continues to listen. `redis` remains `null`.
2. `login.mjs` (lines 9-17): The `createClient().connect()` call fails. The
   module-level `redis` variable stays `null`. The server logs a warning.

**Result:** All `if (redis)` guards in the write/read functions prevent Redis
calls when the client is unavailable. The system continues in standalone mode:

- All user presence is local in-memory only
- Cross-instance features are unavailable (users on other instances are invisible)
- `io.emit()` and `io.to(room).emit()` only reach this instance's clients
- All 12 standalone unit tests pass without Redis

**There is no retry logic.** If Redis becomes available after the process started,
the system stays in standalone mode until the process is restarted.

### Room Membership vs User Presence

These are two separate concepts with different storage backends:

| Concept | Managed By | Stored In | What It Tracks |
|---------|-----------|-----------|----------------|
| Socket.IO room membership | `@socket.io/redis-adapter` | Redis (internal adapter keys) | Which socket is in which room — used by `io.to(room).emit()` and `socket.to(room).emit()` |
| User online presence | `login.mjs` | Redis hash `users` | Which user is online/away/inMeeting — used by `getOnlineUSer()` and the browser UI |

The adapter handles room routing automatically — the application never needs to
query or modify room membership in Redis. Room names (from `loginUser()` via
`socket.join(room)`) are tracked by the adapter internally and replicated across
instances.

### Redis Is Optional

Redis is completely optional. The decision tree:

```
REDIS_ENABLED not "true"         → standalone mode, no Redis used
REDIS_ENABLED="true" + connected → cluster mode, full cross-instance features
REDIS_ENABLED="true" + failed    → standalone mode with warning in console
```

In standalone mode, all 12 unit tests pass. The 8 cluster tests require a running
Redis instance (auto-detected or Docker-spawned by the test helper).

---

## Redis Interaction Reference

Complete list of all Redis operations in the application code:

| File | Lines | Operation | Occurs In | Read/Write |
|------|-------|-----------|-----------|------------|
| `WebsocketServer.mjs` | 156-172 | Create pub+sub clients, install adapter | Module init (top-level await) | — |
| `login.mjs` | 11-12 | Create client, connect | Module init (top-level await) | — |
| `login.mjs` | 38 | `hGet("users", userId)` | `loginUser()` | Read |
| `login.mjs` | 41 | `hSet("users", userId, JSON)` | `loginUser()` | Write |
| `login.mjs` | 72 | `hGet("users", userId)` | `disconnectUser()` | Read |
| `login.mjs` | 77 | `hDel("users", userId)` | `disconnectUser()` (count=0) | Delete |
| `login.mjs` | 81 | `hSet("users", userId, JSON)` | `disconnectUser()` (count>0) | Write |
| `login.mjs` | 100 | `hGet("users", userId)` | `setStatus()` | Read |
| `login.mjs` | 106 | `hSet("users", userId, JSON)` | `setStatus()` | Write |
| `login.mjs` | 124 | `hGet("users", userId)` | `stillOnline()` | Read |
| `login.mjs` | 129 | `hSet("users", userId, JSON)` | `stillOnline()` | Write |
| `login.mjs` | 147 | `hGet("users", userId)` | `enterMeeting()` | Read |
| `login.mjs` | 152 | `hSet("users", userId, JSON)` | `enterMeeting()` | Write |
| `login.mjs` | 170 | `hGet("users", userId)` | `leaveMeeting()` | Read |
| `login.mjs` | 175 | `hSet("users", userId, JSON)` | `leaveMeeting()` | Write |
| `login.mjs` | 192 | `hGet("users", userId)` | `getUserStatus()` | Read |
| `login.mjs` | 216 | `hGet("users", userId)` | `checkEmptySockets()` | Read |
| `login.mjs` | 239 | `hGet("users", userId)` | `setAwayTime()` | Read |
| `login.mjs` | 244 | `hSet("users", userId, JSON)` | `setAwayTime()` | Write |
| `login.mjs` | 258 | `hGet("users", id)` in loop | `getStatusForListOfIds()` | Read |
| `login.mjs` | 298 | `hGetAll("users")` | `getOnlineUSer()` | Read |
| `login.mjs` | 354 | `hGetAll("users")` | Stale cleanup interval | Read |
| `login.mjs` | 361 | `hDel("users", id)` | Stale cleanup interval | Delete |
| `WebsocketServer.mjs` | 288 | `hGet("users", userId)` | Heartbeat interval | Read |
| `WebsocketServer.mjs` | 304 | `hSet("users", userId, JSON)` | Heartbeat interval | Write |

**Summary:**

- **Reads:** 14 call sites (11 in `login.mjs`, 1 in `WebsocketServer.mjs`)
- **Writes:** 9 call sites (7 in `login.mjs`, 1 in `WebsocketServer.mjs`)
- **Deletes:** 2 call sites (both in `login.mjs`)
- **Key used:** `"users"` — the only application-level key

---

## How to Inspect Redis at Runtime

### Inside DDEV

```bash
# Total number of connected users across all instances
ddev exec -s redis redis-cli HLEN users

# Get a specific user's data
ddev exec -s redis redis-cli HGET users "some-user-uid"

# Get ALL user data (may be large in production)
ddev exec -s redis redis-cli HGETALL users

# Check which users are online (parse JSON manually)
ddev exec -s redis redis-cli HGETALL users | paste - - | while read uid json; do
  status=$(echo "$json" | jq -r '.status // "unknown"')
  sockets=$(echo "$json" | jq -r '.socketsCount // 0')
  echo "$uid: $status (tabs: $sockets)"
done

# View Socket.IO adapter keys (internal — do not modify)
ddev exec -s redis redis-cli KEYS "socket.io*"

# Monitor all Redis commands in real time (verbose)
ddev exec -s redis redis-cli MONITOR
```

### Quick Health Check (curl)

```bash
# From inside DDEV
ddev exec -s redis redis-cli PING
# Expected: PONG

# Check memory usage
ddev exec -s redis redis-cli INFO memory | grep used_memory_human
```
