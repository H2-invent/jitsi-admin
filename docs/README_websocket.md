# WebSocket Server — Architecture, Configuration, and Testing

This document describes the jitsi-admin WebSocket server, its active/active clustering
capabilities, configuration options, internal logic, and testing procedures.

## Table of Contents

- [Architecture Overview](#architecture-overview)
- [File Structure](#file-structure)
- [Data Flow](#data-flow)
- [Configuration](#configuration)
- [TLS &amp; Certificate Handling](#tls--certificate-handling)
- [Active/Active Clustering with Redis](#activeactive-clustering-with-redis)
  - [Redis Connection Model](#redis-connection-model)
  - [Redis Pub/Sub vs Key/Value](#redis-pubsub-vs-keyvalue)
- [Topic Naming &amp; Subscription](#topic-naming--subscription)
- [Load Balancers &amp; Sticky Sessions](#load-balancers--sticky-sessions)
  - [Without a Load Balancer](#without-a-load-balancer) (single instance)
  - [Multi-Instance Without a Load Balancer](#multi-instance-without-a-load-balancer)
  - [When to Use a Load Balancer](#when-to-use-a-load-balancer)
- [Multi-Tab Behavior](#multi-tab-behavior)
- [Client Reconnect UX](#client-reconnect-ux)
- [User Presence &amp; Online Status Logic](#user-presence--online-status-logic)
- [How to Test](#how-to-test)
  - [Automated Tests (Node.js)](#automated-tests-nodejs)
  - [E2E Test Script (curl + DDEV)](#e2e-test-script-curl--ddev)
  - [Manual Testing via Browser](#manual-testing-via-browser)
  - [Manual Testing via curl](#manual-testing-via-curl)
- [Troubleshooting](#troubleshooting)
- [DDEV Development Setup](#ddev-development-setup)
- [Installation &amp; Deployment](#installation--deployment)

---

## Architecture Overview

The WebSocket system has three layers:

```
┌─────────────────┐      ┌──────────────────────┐      ┌─────────────────┐
│   PHP Backend   │ POST │  Node.js Server(s)    │  WS  │  Browser Client │
│  (Symfony)      │─────>│  (Socket.IO + Redis)  │<────>│  (Socket.IO)    │
│  DirectSendSvc  │      │  server.mjs           │      │  websocket.js   │
└─────────────────┘      └──────────────────────┘      └─────────────────┘
                                 │
                           ┌─────┴─────┐
                           │   Redis    │  (optional, for active/active)
                           └───────────┘
```

### Components

| Component | File | Role |
|-----------|------|------|
| **Server entry point** | `nodejs/server.mjs` | HTTP/S server, Socket.IO setup, Redis adapter init, JWT auth, heartbeat |
| **Config** | `nodejs/config.mjs` | All environment-variable-driven settings |
| **User session manager** | `nodejs/login.mjs` | User state (local in-memory + optional Redis), presence functions |
| **User model** | `nodejs/User.mjs` | Per-user state: sockets array, status, away timer, meeting tracking |
| **Event dispatcher** | `nodejs/websocketState.mjs` | Routes incoming Socket.IO events to the appropriate handlers |
| **IO registry** | `nodejs/ioRegistry.mjs` | Shared `io` instance reference to break circular dependency |
| **Client library** | `assets/js/websocket.js` | Browser-side Socket.IO connection, `mercure` event listener |
| **PHP publish service** | `src/Service/Lobby/DirectSendService.php` | Publishes messages via Symfony's `HubInterface` to the Mercure POST endpoint |
| **PHP JWT service** | `src/Service/Websocket/WebsocketJwtService.php` | Creates JWT tokens for browser WebSocket authentication |

---

## File Structure

```
nodejs/
├── server.mjs              # Main entry point — HTTP server, Socket.IO, Redis adapter
├── config.mjs              # Environment variable config
├── login.mjs               # User session management (local + Redis)
├── User.mjs                # Per-user state class
├── websocketState.mjs      # Event routing (setStatus, enterMeeting, etc.)
├── ioRegistry.mjs          # Shared io instance (breaks circular import)
├── checkCertAndKey.js      # TLS cert/key presence check
├── Dockerfile              # Dev Docker build
├── Dockerfile-prod         # Production Docker build
├── package.json            # Node.js dependencies and scripts
├── config/
│   └── websocket.service   # systemd unit file
└── tests/
    ├── websocket.test.mjs          # 12 standalone unit tests (no Redis needed)
    ├── websocket-cluster.test.mjs  # 8 active/active cluster tests (requires Redis)
    ├── e2e-test.sh                 # Bash end-to-end test for DDEV/bare metal
    └── helpers/
        └── redis-helper.mjs        # Redis connection helper for cluster tests
```

---

## Data Flow

### Client Connects

```
Browser                          Node.js (server.mjs)                    Redis (login.mjs)
  │                                     │                                     │
  │── WS connect + JWT ────────────────>│                                     │
  │                                     │── JWT verify ──────────────────────>│
  │                                     │── loginUser(socket)                 │
  │                                     │   ├─ Create local User object       │
  │                                     │   └─ Write to Redis (hset users) ──>│
  │<── sendUserStatus ─────────────────│                                     │
  │<── sendOnlineUser (io.emit) ───────│                                     │
```

### PHP Publishes a Message

```
PHP Backend                  Node.js (POST /)              Redis Adapter      Browser Client
  │                              │                            │                    │
  │── POST data+topic ──────────>│                            │                    │
  │   (JWT auth)                 │── io.to(room).emit()       │                    │
  │                              │   (Redis adapter fans      │                    │
  │                              │    out to all instances)──>│                    │
  │                              │                            │── to sockets ─────>│
  │<── 200 OK ──────────────────│                            │                    │
```

### Active/Active Message Flow

```
PHP Backend
    │
    │ POST /.well-known/mercure  (to any instance)
    ▼
┌───────────┐    Redis Pub/Sub    ┌───────────┐
│ Instance 1│<───────────────────>│ Instance 2│
│ io.to()   │                     │ io.to()   │
└─────┬─────┘                     └─────┬─────┘
      │                                 │
      ▼                                 ▼
┌───────────┐                     ┌───────────┐
│ Browser A │                     │ Browser B │
└───────────┘                     └───────────┘
```

Key: `@socket.io/redis-adapter` makes `io.to(room).emit()` and `io.emit()` work
across all instances transparently. The PHP backend only needs to POST to ONE
instance — the Redis adapter ensures delivery to all connected sockets regardless
of which instance holds them.

---

## Configuration

All WebSocket server configuration is in `nodejs/config.mjs` and driven by
environment variables.

### Core Settings

| Env Variable | Default | Description |
|-------------|---------|-------------|
| `WEBSOCKET_SECRET` | `MY_SECRET` | **Must be changed in production.** Shared secret for JWT signing/verification. Must match between PHP and Node.js |
| `PORT` | `3000` | HTTP/HTTPS listen port |
| `KEY_FILE` | `./tls_certificate/key.pem` | Path to TLS private key file |
| `CERT_FILE` | `./tls_certificate/cert.pem` | Path to TLS certificate file |
| `MERCURE_INTERNAL_URL` | `/.well-known/mercure` | POST endpoint URL for the PHP→Node.js Mercure bridge |
| `AWAY_TIME` | `5` | Minutes of inactivity before user is marked as "away" |
| `DEFAULT_STATE` | `offline` | Default status for new users |

### Redis / Cluster Settings

| Env Variable | Default | Description |
|-------------|---------|-------------|
| `REDIS_ENABLED` | (empty, `false`) | Set to `"true"` to enable active/active clustering via Redis |
| `REDIS_HOST` | `redis` | Redis server hostname |
| `REDIS_PORT` | `6379` | Redis server port |

### PHP Side Settings (`.env`)

| Env Variable | Description |
|-------------|-------------|
| `MERCURE_URL` | URL of the WebSocket server's Mercure POST endpoint (e.g., `http://websocket:3000/.well-known/mercure`) |
| `MERCURE_PUBLIC_URL` | Public URL browsers use to connect (e.g., `https://jitsi-admin.ddev.site:3000`) |
| `MERCURE_JWT_SECRET` | Secret for Mercure publish JWTs |
| `WEBSOCKET_SECRET` | Must match the Node.js `WEBSOCKET_SECRET` |

### Example: Standalone (No Redis)

```bash
# .env
WEBSOCKET_SECRET=my-production-secret
PORT=3000
```

### Example: Active/Active Cluster

```bash
# .env
WEBSOCKET_SECRET=my-production-secret
PORT=3000
REDIS_ENABLED=true
REDIS_HOST=redis
REDIS_PORT=6379
```

The Redis client will connect to `redis://${REDIS_HOST}:${REDIS_PORT}`. The `@socket.io/redis-adapter`
takes two separate Redis connections (pubClient + subClient) for cross-instance
Socket.IO communication.

---

## TLS &amp; Certificate Handling

### Auto-Detection

The server (`server.mjs` lines 22-43) automatically detects whether to start as HTTP
or HTTPS by checking for valid TLS certificate files:

1. `checkFileContains(CERT_FILE, 'BEGIN CERTIFICATE')` — verifies the cert file exists
   and contains PEM-format data
2. `checkFileContains(KEY_FILE, 'BEGIN PRIVATE KEY')` — verifies the key file exists
   and contains PEM-format data
3. If both checks pass → **HTTPS server** starts
4. If either check fails → **HTTP server** starts (plain text)

The check (`nodejs/checkCertAndKey.js`) looks for ASCII magic strings in the files
using `fs.existsSync()` + `fs.readFileSync()`. Binary certificate formats (DER,
PKCS#7) will fail the check, causing an HTTP fallback. Permission errors on
`fs.readFileSync()` also trigger fallback to HTTP with a console error:
`"HTTPS Setup fehlgeschlagen:"`.

### Deployment Patterns

| Environment | TLS Handling | Notes |
|------------|-------------|-------|
| DDEV | Traefik terminates TLS | `docker-compose.websocket.yaml` exposes port 3000 as HTTPS via `HTTPS_EXPOSE` |
| Docker (production) | Traefik terminates TLS | `docker-compose.cluster.yml` — Traefik handles `wss://` at the edge |
| Bare-metal (systemd) | Node.js handles TLS directly | `KEY_FILE`/`CERT_FILE` must point to valid PEM files. `install.sh` does not auto-generate certs |
| Single-instance Docker | Node.js handles TLS or Traefik | `Dockerfile-prod` exposes plain HTTP port 3000; TLS is terminated externally |

### Recommended Setup

For production, terminate TLS at the load balancer (Traefik/nginx) and have the
Node.js server listen on plain HTTP internally. This avoids certificate management
inside the Node.js process and allows the load balancer to handle `wss://` upgrades.
Set `MERCURE_PUBLIC_URL` to the HTTPS URL and `MERCURE_URL` to the internal HTTP
URL where PHP reaches the node directly.

### JWT Secret Management

**Two separate JWT secrets exist** in the configuration, but in practice they are
set to the same value by the installer:

| Secret | Used By | Purpose |
|--------|---------|---------|
| `MERCURE_JWT_SECRET` | Symfony Mercure bundle (PHP) | Signs JWTs for the PHP → Node.js Mercure POST endpoint |
| `WEBSOCKET_SECRET` | Node.js server + `WebsocketJwtService` (PHP) | Signs JWTs for browser → Node.js Socket.IO connections |

Both the `installDocker.sh` and bare-metal installers set `WEBSOCKET_SECRET=$MERCURE_JWT_SECRET`,
so they share the same value. The Node.js server verifies both types of JWTs against
`WEBSOCKET_SECRET`.

The `mercure.yaml` configuration grants `publish: '*'` — any valid JWT can publish
to any topic. There is no per-topic ACL on the Node.js side either; the server only
verifies JWT signature, not topic permissions.

### Known Issue: `getUrlforWebsocket()` Dead Code

The `src/Twig/WebsocketJwt.php::getUrlforWebsocket()` method contains a dead-code
bug: it attempts to replace `https` → `wss` (or `http` → `ws`) using `str_replace()`,
but the return value is never assigned — the function always returns the original
`MERCURE_PUBLIC_URL`. Socket.IO client handles scheme upgrades automatically, so
this is harmless in practice.

---

## Active/Active Clustering with Redis

### How It Works

When `REDIS_ENABLED=true`, the server uses two Redis mechanisms:

1. **`@socket.io/redis-adapter`**: Handles cross-instance Socket.IO operations.
   - `io.emit()` — message reaches ALL sockets across ALL instances
   - `io.to(room).emit()` — message reaches socket.io room members across all instances
   - `socket.join()` / `socket.leave()` — room membership shared across instances
   - Installed in `server.mjs` lines 60-76

2. **Shared User State (Redis Hash)**: `login.mjs` maintains a `users` Redis hash
   for cross-instance presence. Each user's data is stored as a JSON object:

   ```json
   {
     "id": "user-uid-123",
     "status": "online",
     "socketsCount": 2,
     "inMeetingCount": 1,
     "away": false,
     "awayTime": 5,
     "updatedAt": 1710435600000
   }
   ```

   - `socketsCount` — number of browser tabs connected for this user (across all instances)
   - `inMeetingCount` — number of tabs currently in a Jitsi meeting
   - `away` — whether the away timer has fired
   - `updatedAt` — timestamp of last state update (used for stale cleanup)

### Dual-State Architecture

Each function in `login.mjs` follows a consistent pattern:

1. **Always** update the local in-memory `user` object (for socket operations like `sendToAllSockets()`)
2. **Additionally** sync to Redis (for cross-instance presence)

This ensures that socket-specific operations (which hold native TCP handles) work
locally, while presence data is shared globally.

### Heartbeat

When Redis is enabled, `server.mjs` runs a heartbeat every 10 seconds that refreshes
each locally-connected user's entry in the Redis `users` hash (**line 140-170**).
This keeps `updatedAt` fresh so that:

- Other instances see the user as currently active
- The stale user cleanup doesn't remove them

### Stale User Cleanup

`login.mjs` runs a cleanup interval every 60 seconds (**lines 351-369**) that removes
users from Redis whose `updatedAt` is older than 2 minutes. This handles orphaned
entries from crashed instances.

### Redis Connection Model

The system uses **persistent** Redis connections — they are established once at
process startup and held open for the entire lifetime of the Node.js process.
There are three connections total:

| Connection | Created In | Lifecycle | Used For |
|-----------|-----------|-----------|----------|
| `pubClient` (adapter) | `server.mjs` top-level await | Persistent — process lifetime | Publishes Socket.IO messages to other instances. Also reused for the heartbeat and by `login.mjs` for KV operations |
| `subClient` (adapter) | `pubClient.duplicate()` | Persistent — process lifetime | Subscribes to Socket.IO messages from other instances (adapter internal) |
| `login.mjs` client | `login.mjs` top-level await | Persistent — process lifetime | User presence KV operations (`hGet`/`hSet`/`hGetAll`/`hDel`) |

All three connections are created during the top-level module evaluation. The
`await` on `client.connect()` blocks the server from starting until Redis is
reachable. Once connected, the connections remain open for the process lifetime.
The `redis` v5 client has built-in auto-reconnect with exponential backoff —
if Redis restarts, the connections automatically re-establish.

**There are no on-demand connections** — no "connect when needed, disconnect when
done" patterns exist anywhere in the code. Every Redis operation calls a method
on an already-connected, long-lived client. This is the standard pattern for the
`redis` v5 library and avoids the TCP handshake overhead of opening/closing
connections on every operation.

**Why persistent instead of on-demand?** The Pub/Sub connection used by the
adapter MUST be persistent — Redis Pub/Sub requires the subscriber to maintain
a long-lived connection in `SUBSCRIBE` mode. It is not possible to subscribe,
receive one message, then disconnect. For the KV operations (`login.mjs`), the
connection could technically be opened per-operation, but this would add TCP
handshake latency (~1-5ms to local Redis) to every `hGet`/`hSet` call — which
happens on every connect, disconnect, status change, meeting enter/leave, and
heartbeat tick. The persistent approach eliminates this overhead.

### Redis Pub/Sub vs Key/Value — Two Mechanisms, One Redis

The system uses **two different Redis mechanisms** for different purposes:

#### Pub/Sub (used by `@socket.io/redis-adapter`)

```
Instance A                        Redis Pub/Sub channel              Instance B
    │                               │                               │
    │── io.to("room").emit(data)    │                               │
    │── PUBLISH channel payload ───>│                               │
    │                               │── PUBLISHED to subscribers ──>│
    │                               │                               │── deliver to local sockets
```

This is **ephemeral fire-and-forget messaging** — messages are pushed to all
subscribers in real time and then discarded. If an instance isn't subscribed at
the moment a message is published, it misses it (which is fine — if no client
is connected to that instance, there's no one to deliver to).

The adapter requires **two separate connections** because Redis Pub/Sub changes
the connection protocol: once in `SUBSCRIBE` mode, a connection can only receive
messages, not send `PUBLISH` commands. Hence: pubClient (for publishing) and
subClient (for subscribing), created at `server.mjs` lines 65-66.

#### Key/Value Hash (used by `login.mjs`)

```js
// These are standard Redis hash commands, not Pub/Sub:
await redis.hGet("users", userId);       // Read a user's presence
await redis.hSet("users", userId, JSON); // Write a user's presence
await redis.hGetAll("users");            // Read ALL users' presence
await redis.hDel("users", userId);       // Remove a disconnected user
```

This is **durable state storage** — data persists in the Redis hash until
explicitly deleted or cleaned up by the stale sweep. Instances read the hash
to discover users on other instances. Unlike Pub/Sub, the data survives
instance restarts and can be queried at any time.

#### Why Both Are Needed

| Problem | Solution | Why Pub/Sub alone isn't enough | Why KV alone isn't enough |
|---------|----------|-------------------------------|---------------------------|
| "Instance A just called `io.emit()`. Instance B needs to know immediately." | Redis Pub/Sub (adapter) | — | KV requires polling (Instance B would need to constantly `HGETALL` to detect changes — no push notification) |
| "Instance B just restarted. Who is online right now?" | Redis KV hash (`HGETALL users`) | Pub/Sub is ephemeral — no history, no state. Instance B missed all the `PUBLISH` messages while restarting | — |

Pub/Sub delivers **events** (push-based, ephemeral, immediate). KV delivers
**state** (pull-based, durable, queryable). The architecture needs both: the
adapter's Pub/Sub for real-time cross-instance Socket.IO message relay, and
the `users` hash for cross-instance presence queries that work at any time,
including after instance restarts.

---

## Topic Naming &amp; Subscription

The WebSocket system uses Socket.IO **rooms** as its pub/sub mechanism. When a browser
connects, the JWT's `rooms` array is used to call `socket.join(room)` for each entry.
Messages published via the Mercure POST endpoint are delivered to the corresponding room.

### Topic Patterns

| Topic Pattern | Page | Subscribers | Purpose |
|--------------|------|-------------|---------|
| `personal/{userUid}` | Dashboard | The user themselves | Personal notifications, dashboard refresh, deputy changes |
| `whiteboard/{roomUid}` | Start page | Conference participants | Whiteboard open-for-all commands |
| `lobby_moderator/{roomUid}` | Lobby moderator page | Moderators only | New-waiting-user notifications, lobby refreshes |
| `lobby_WaitingUser_websocket/{userUid}` | Lobby participant page | Individual waiting users | Accept/decline notifications, redirects |
| `lobby_personal{roomUid}{userUid}` | Lobby moderator page | The logged-in user specifically | Personal messages within the lobby context |
| `lobby_broadcast_websocket/{roomUid}` | Lobby participant page | All lobby participants | End-meeting broadcasts, mass notifications |
| `{roomUid}` (raw) | Lobby pages | All room participants | Room-scoped events (iframe open, general updates) |

**JWT rooms and Mercure topics are separate concepts but share the same namespace.**
The JWT `rooms` array controls which Socket.IO rooms a browser subscribes to on
connect. PHP publishes `Update` objects with Mercure `topic` strings. The Node.js
server translates these topics 1:1 into `io.to(topic).emit("mercure", data)` —
effectively using the Mercure topic as a Socket.IO room name.

### Topic Construction Per Page

| Twig Template | Topics Subscribed | Notes |
|--------------|-------------------|-------|
| `dashboard/index.html.twig` | `personal/{app.user.uid}` | Personal dashboard |
| `start/index.html.twig` | `whiteboard/{room.uidReal}` | Conference start page |
| `lobby/index.html.twig` | `lobby_moderator/{uid}`, `lobby_broadcast_websocket/{uid}`, `lobby_personal{uid}{userUid}`, raw `{uid}` | Moderator lobby (4 topics if authenticated, 3 if not) |
| `lobby/livekit.html.twig` | Same as lobby | LiveKit-backed lobby variant |
| `lobby_participants/index.html.twig` | `lobby_WaitingUser_websocket/{uid}`, `lobby_broadcast_websocket/{roomUid}` | Waiting user lobby (2 topics) |
| `join/base.html.twig` | `[]` (empty) | Join page — no rooms, only Mercure global |

### `openNewIframe` — Pure Socket.IO Architecture

The `openNewIframe` event is the **only event that completely bypasses the PHP/Mercure
pipeline**. It uses a pure Socket.IO client-to-server-to-clients flow:

1. A moderator clicks an "open for all" button in the conference UI (e.g., whiteboard, etherpad)
2. The browser emits `openNewIframe` with `{ room, url, title }` via `sendViaWebsocket()`
3. `websocketState.mjs` handles it: `socket.to(room).emit('openNewIframe', ...)` — this sends to
   ALL sockets in the room **except the sender**
4. With the Redis adapter, `socket.to(room)` works across instances transparently
5. Receiving clients parse the URL for a `%name%` placeholder (replaced with the
   display name from a Twig global `schowNameInWidgets`)
6. If the client is inside an iframe (`inIframe()`), it uses `window.parent.postMessage()`
   to route the request to the top-level window
7. The top-level window calls `createIframe()` to open the external application

The two sources that trigger `openNewIframe` are:
- `createConference.js` — multiframe conference UI ("open for all" buttons)
- `startWhiteboard.js` — standalone whiteboard start buttons (`.startExternalApp` elements)

### PHP Message Types

The `DirectSendService` generates Mercure `Update` objects with a `type` field that
the browser's `lobbyNotification.js::masterNotify()` dispatches:

| `type` Value | Browser Action |
|-------------|---------------|
| `snackbar` | Shows a toast notification (color/size configurable) |
| `notification` | Shows a snackbar AND triggers a browser push notification |
| `browserPush` | Triggers a browser push notification only |
| `playSound` | Plays a notification/ringtone sound |
| `cleanNotification` | Dismisses a toast by message ID |
| `refresh` | AJAX reloads lobby waiting user list |
| `modal` | Shows a Bootstrap modal dialog |
| `redirect` | Redirects the browser to a URL after a timeout |
| `snackbar` | Shows a toast notification |
| `dialog` | Shows an interactive dialog (used for ad-hoc meeting invites) |
| `refreshDashboard` | Triggers dashboard page reload |
| `endMeeting` | Triggers the meeting end dialog |
| `reload` | Full page reload |
| `call` | Rings continuously + push notification (ad-hoc call) |
| `message` | Appends a chat message to the message container |

---

## Load Balancers & Sticky Sessions

### Where a Load Balancer Fits in the Architecture

In a production deployment with multiple WebSocket server instances, a load balancer
(such as Traefik, nginx, HAProxy, or a cloud provider's ALB/NLB) sits in front of
the Node.js instances:

```
                           ┌─────────────────┐
                           │  Load Balancer   │
                           │  (Traefik/nginx) │
                           │  Port 443 (TLS)  │
                           └────────┬────────┘
                                    │
                    ┌───────────────┼───────────────┐
                    │               │               │
              ┌─────┴─────┐   ┌─────┴─────┐   ┌─────┴─────┐
              │ Instance 1│   │ Instance 2│   │ Instance 3│
              │  Port 3000│   │  Port 3000│   │  Port 3000│
              └─────┬─────┘   └─────┬─────┘   └─────┬─────┘
                    │               │               │
                    └───────────────┼───────────────┘
                                    │
                           ┌────────┴────────┐
                           │     Redis        │
                           │  (shared state)  │
                           └─────────────────┘
```

**Browser always connects to the load balancer**, which forwards the WebSocket
connection to one of the backend instances. The browser never knows which instance
it's connected to — it only sees the load balancer's URL.

**PHP always POSTs to the load balancer** (or directly to an internal instance URL,
depending on network topology). In either case, the `@socket.io/redis-adapter`
ensures the message reaches all connected sockets across all instances.

### Without a Load Balancer

In the simplest deployment (single instance, no load balancer):

```
Browser ──────────────────> Node.js (single instance)
         ws://server:3000
```

- The browser connects directly to the WebSocket server
- `MERCURE_PUBLIC_URL` points to the single server
- All clients share the same instance
- No Redis needed (`REDIS_ENABLED=false`, the default)
- Simpler to deploy and debug

This mode is sufficient for low-traffic deployments or development environments.

### Multi-Instance Without a Load Balancer

It is also possible to run **multiple instances without a load balancer**:

```
Browser ────── ws://server-a:3000 ──────> Node.js Instance A
Browser ────── ws://server-b:3000 ──────> Node.js Instance B
```

In this setup, each browser connects directly to a **specific** WebSocket server
URL. The browser discovers which server to connect to via the `websocketUrl`
JavaScript global, which is injected into every page by PHP at page load time
(from the `MERCURE_PUBLIC_URL` env var via `getUrlforWebsocket()`).

**This works correctly without sticky sessions or a load balancer because:**

- **User state is in Redis** — when User X connects to Instance A, their presence
  is written to the shared `users` Redis hash. Instance B reads that same hash and
  knows User X is online even though they are on Instance A.
- **Socket.IO messages are relayed via the Redis adapter** — if the PHP backend
  publishes to a room and the HTTP POST arrives at Instance A, the
  `@socket.io/redis-adapter` fans the `io.to(room).emit()` call out to all
  instances. Instance B delivers it to any room members connected to Instance B.
- **Client-to-client events propagate** — if User X on Instance A changes their
  status, the resulting `io.emit("sendOnlineUser")` reaches all instances via the
  adapter. User Y on Instance B sees the update immediately.

**There is no concept of a "correct" server for a given user.** The architecture
is intentionally server-agnostic: all servers read/write the same Redis state,
so every instance can serve any user. If Instance A goes down, the users on it
disconnect — but users on Instance B and C are unaffected. A page reload on the
affected users' browsers (which generates a new page with an updated
`websocketUrl` pointing to a different instance) brings them back.

**Limitation of the no-LB approach:** The `websocketUrl` is hardcoded per page
load. If the target instance goes down, the browser's WebSocket stays disconnected
until the user reloads the page (which regenerates the URL from a fresh PHP render
that could use a different server list). A load balancer solves this by providing
a single, stable URL that automatically routes to healthy instances.

### When to Use a Load Balancer

| Scenario | Recommendation |
|----------|---------------|
| Single instance | No LB needed |
| Multi-instance, can hardcode URLs per user group | LB optional — works without one |
| Multi-instance, need unified URL | **LB recommended** — single `MERCURE_PUBLIC_URL` for all users |
| Multi-instance, TLS termination | **LB recommended** — handles `wss://` at the edge instead of per-instance certs |
| Multi-instance, automatic failover on instance crash | **LB required** — routes reconnects to healthy instances |
| Multi-instance in Docker via Traefik | **LB included** — Traefik is your LB and reverse proxy |

### With a Load Balancer (Single Instance)

The load balancer adds TLS termination and routing even with one backend:

```
Browser ─────> Load Balancer (TLS) ─────> Node.js (single instance)
         wss://public.url:443         ws://internal:3000
```

- The browser connects via secure WebSocket (`wss://`)
- TLS is terminated at the load balancer — the Node.js server only needs HTTP internally
- `MERCURE_PUBLIC_URL` is set to the public load balancer URL
- PHP's `MERCURE_URL` can point to the internal instance directly (bypassing the LB)

### With a Load Balancer (Multi-Instance Active/Active)

```
Browser A ──┐           ┌─ Instance 1 ───┐
Browser B ──┤── LB ────┤─ Instance 2 ───┤── Redis
Browser C ──┘           └─ Instance 3 ───┘
```

- The load balancer distributes connections across instances
- Each instance handles a subset of browser connections
- `@socket.io/redis-adapter` ensures cross-instance messaging
- Redis holds the shared user presence state
- PHP publishes to the internal Mercure endpoint (which the LB routes to any instance)

### Sticky Sessions: With vs Without

Socket.IO has **two transport modes**: WebSocket (preferred) and HTTP long-polling
(fallback). The long-polling transport sends multiple HTTP requests and requires
**all requests from the same client to reach the same backend instance**.

#### Without Sticky Sessions

```
Client ────> LB ── request 1 ──> Instance 1
Client ────> LB ── request 2 ──> Instance 2  (wrong instance!)
```

| Aspect | Without Sticky Sessions |
|--------|------------------------|
| WebSocket transport | Works correctly. Once upgraded to WS, the TCP connection is pinned |
| Long-polling transport | Connection drops and renegotiates if requests hit different instances. Socket.IO recovers, but with latency |
| Connection churn | Slightly higher. Some clients experience unnecessary reconnects |
| Application-level events | All correct. `io.emit()`, `io.to()`, status changes all work via Redis |
| User presence | Fully correct. Shared Redis state handles cross-instance presence |
| Room membership | Fully correct. Redis adapter synchronizes rooms across instances |
| Server affinity | None required. Any instance can handle any event |

**Key point**: Without sticky sessions, all application-layer behavior is **correct**
because state is in Redis and the adapter handles cross-instance messaging. The
only degradation is that some clients behind restrictive firewalls or proxies may
fall back to long-polling, which causes extra reconnection overhead.

#### With Sticky Sessions

```
Client ────> LB ── request 1 ──> Instance 1
Client ────> LB ── request 2 ──> Instance 1  (same instance!)
```

| Aspect | With Sticky Sessions |
|--------|---------------------|
| WebSocket transport | Same as without |
| Long-polling transport | Works perfectly — all requests hit the same instance |
| Connection churn | Minimal. No unnecessary reconnects |
| Application-level events | Same correctness as without |
| User presence | Same correctness as without |
| Server affinity | Cookie-based, not required but beneficial |
| Instance failure | Sticky session redirects to another instance; reconnects via Redis state |

### Implementing Sticky Sessions

#### Traefik

```yaml
# docker-compose or static config
labels:
  - "traefik.http.services.websocket.loadbalancer.sticky.cookie=true"
  - "traefik.http.services.websocket.loadbalancer.sticky.cookie.name=websticky"
  - "traefik.http.services.websocket.loadbalancer.sticky.cookie.httpOnly=true"
  - "traefik.http.services.websocket.loadbalancer.sticky.cookie.secure=true"
```

#### nginx

```nginx
upstream websocket_backend {
    ip_hash;  # sticky by client IP
    # OR: hash $cookie_websticky consistent;
    server instance1:3000;
    server instance2:3000;
    server instance3:3000;
}

server {
    listen 443 ssl;
    server_name jitsi-admin.example.com;

    location /ws/ {
        proxy_pass http://websocket_backend;
        proxy_http_version 1.1;
        proxy_set_header Upgrade $http_upgrade;
        proxy_set_header Connection "upgrade";
        proxy_set_header Host $host;
        proxy_read_timeout 86400s;
    }
}
```

#### HAProxy

```
backend websocket_servers
    balance leastconn
    cookie WEBSOCKETID insert indirect nocache httponly secure
    server srv1 instance1:3000 cookie s1 check
    server srv2 instance2:3000 cookie s2 check
    server srv3 instance3:3000 cookie s3 check
```

### Recommendations

| Deployment Type | Sticky Sessions | Rationale |
|----------------|-----------------|-----------|
| Development / DDEV | Not needed | Single instance, no load balancer |
| Single production instance | Not needed | All clients on same instance |
| Multi-instance, all clients support WebSocket | Not needed (optional) | Works correctly; adds stability |
| Multi-instance, mixed client base | **Recommended** | Ensures long-polling fallback works on restrictive networks |
| Multi-instance, high availability critical | **Recommended** | Reduces reconnection churn during instance restarts |

**Summary**: Sticky sessions are a **best practice for Socket.IO deployments**
because they ensure the long-polling transport fallback works reliably. However,
this application's architecture with Redis-based state sharing and `@socket.io/redis-adapter`
means that **all core functionality works correctly without sticky sessions**.
The choice depends on your client base and tolerance for occasional long-polling
reconnection latency.

### PHP POST Routing

When publishing messages, the PHP backend POSTs to `MERCURE_URL`. In a multi-instance
setup:

- **POST to load balancer**: The LB routes the POST to any available instance.
  The receiving instance publishes via `io.to(room).emit()`, which the Redis adapter
  fans out to all instances. ✓ Works correctly regardless of which instance receives the POST.

- **POST directly to an internal instance URL**: Same effect — the Redis adapter
  handles cross-instance fan-out.

- **POST to all instances (round-robin)**: NOT needed and would cause duplicate
  message delivery. The adapter already handles cross-instance delivery.

The PHP side requires **no changes** for active/active — `DirectSendService` keeps
posting to a single Mercure URL, and the adapter handles the rest.

---

## Multi-Tab Behavior

When a user opens multiple browser tabs for the same jitsi-admin page:

1. **Each tab establishes a separate WebSocket connection** with the same JWT (same `sub`)
2. `login.mjs` accounts for this via `socketsCount` — the counter increments per tab
3. The user only goes offline when `socketsCount` drops to zero (all tabs disconnected)
4. **Notification deduplication**: If a `mercure` notification arrives (browser push,
   sound playback), `lobbyNotification.js` uses `TabUtils.lockFunction()` from
   `tabBroadcast.js` to ensure it only plays in ONE tab. This uses `localStorage`
   as a cross-tab mutex with a 2.5-second lock.
5. **Status changes**: Status changes (`setStatus`, `enterMeeting`) affect the user
   globally — all tabs reflect the same status (synced to Redis in cluster mode).
6. **Away timer**: The `stillOnline` event is sent from every visible tab every 2
   seconds. As long as at least one tab is visible, the away timer stays reset.

### Tab Visibility & Away Detection

- `stillOnline` is sent on a **2-second polling interval** via `setInterval`, not
  only on Page Visibility API events
- When ALL tabs are hidden (not in any visible window), `stillOnline` stops being sent
- After `AWAY_TIME` minutes (default: 5), the servers away timer fires and marks
  the user as "away"
- When at least one tab becomes visible again, `stillOnline` resumes and resets the
  away timer (user goes back to "online")
- **Known limitation**: If the browser doesnt support the Page Visibility API
  (very old browsers), `stillOnline` is never sent, and the user is permanently
  marked "away" after the first away timer fires. Only a manual `setStatus` or
  page reload can reset this.
- Users can configure their own away timeout via the UI (`#awayTimeField`), which
  sends `setAwayTime` via WebSocket (debounced at 500ms). This value is stored in
  Redis in cluster mode.
- Browser notification pushes (via `Push.js` or the `Notification` API) are only
  triggered when `document.visibilityState === hidden`. If the tab is visible,
  the notification appears as an in-page snackbar instead. Sound always plays
  regardless of visibility.

### `websocketReady` Flag (Lobby Participants)

In the lobby participant flow, an extra HTTP request signals that the browsers
WebSocket connection is ready to receive messages:

1. The template `lobby_participants/index.html.twig` sets `urlWebsocketReady` as a
   JavaScript global, pointing to a Symfony route
2. `websocket.js` line 24-29: on `socket.on(connect)`, if `urlWebsocketReady` is
   defined, it sends an `XMLHttpRequest` GET to that URL
3. The backend marks `LobbyWaitungUser::$websocketReady = true` in the database
4. The moderator page checks this flag to determine whether a waiting users client
   can receive real-time messages before sending accept/decline

The `urlWebsocketReady` variable is **only defined in the lobby participant template**.
All other pages have it undefined and skip this step.

---

## Client Reconnect UX

The browser WebSocket client (`assets/js/websocket.js`) implements a user-visible
reconnect notification system:

- On `socket.io "error"`: after a 5-second debounce (to avoid false alarms during
  brief network blips), shows a persistent red snackbar:
  *"Websocket Error. There is no real time communication at the moment. Please reload the page."*
  (30-second display, `socketAlert` ID)

- On `socket.io "reconnect"`:
  - If the error countdown is still active (under 5 seconds), it clears the timer
    and the error is suppressed (the blip was brief enough)
  - Otherwise, it shows a green success snackbar: *"Websocket successfully reconnected"*

- **No automatic token refresh**: JWTs expire after 3 days. If a user leaves a tab
  open for >3 days, the Socket.IO connection breaks on next reconnect attempt and
  cannot be restored without a full page reload (which regenerates the JWT via PHP).

- **No `reconnectionAttempts` or `reconnectionDelay` configuration**: The Socket.IO
  client uses all default values (infinite reconnect, exponential backoff starting
  at 1s, max 5s). There is no way to tune these from PHP configuration.

- **Token passed via `query`, not `auth`**: `websocket.js` passes the JWT as
  `query: {token}` rather than the recommended `auth` handshake field. The token
  is generated at page load and never refreshed.

- `websocketUrl` is a **PHP-injected global variable** (set by Twig templates before
  the JS bundle executes). If script ordering changes, this will silently be `undefined`,
  causing the WebSocket connection to fail.

---

## User Presence & Online Status Logic

### Status Hierarchy

```
online → away → inMeeting → offline
```

| Status | Condition |
|--------|-----------|
| `offline` | No sockets connected (`socketsCount === 0`) or `User.offline === true` |
| `inMeeting` | At least one socket has called `enterMeeting` |
| `away` | Away timer fired (no activity for `AWAY_TIME` minutes) |
| `online` | Connected and none of the above apply |

### Status Transitions

| Event | What Happens |
|-------|-------------|
| Client connects | `loginUser()` creates User, emits `sendUserStatus`, broadcasts `sendOnlineUser` |
| Client disconnects | `disconnectUser()` removes socket; after 7s delay, if all sockets gone, broadcasts "Send is Offline" |
| `setStatus` event | Updates local User + syncs to Redis. Manual status change resets `away = false` |
| `enterMeeting` event | Increments `inMeetingCount` local + Redis, broadcasts updated list |
| `leaveMeeting` event | Decrements `inMeetingCount` local + Redis |
| `stillOnline` event | Every 2s from client (Page Visibility API), resets away timer |
| Away timer fires | `initUserAway()` sets `away = true` after `AWAY_TIME * 60 * 1000` ms |

### Socket.IO Events Reference

#### Client → Server

| Event | Payload | Description |
|-------|---------|-------------|
| `setStatus` | `string` (e.g. `"online"`, `"away"`, `"offline"`, `"busy"`) | Change own status |
| `getStatus` | (none) | Request full online user list (`io.emit("sendOnlineUser")`) |
| `getMyStatus` | (none) | Request own status (`socket.emit("sendUserStatus")`) |
| `stillOnline` | (none) | Reset away timer (sent every 2s by client) |
| `enterMeeting` | (none) | Mark as in-meeting |
| `leaveMeeting` | (none) | Unmark in-meeting |
| `openNewIframe` | `{ room, url, title }` | Open iframe in a room's participants |
| `giveOnlineStatus` | `JSON.stringify(["uid1","uid2",...])` | Request status for specific user IDs |
| `setAwayTime` | `number` (minutes) | Set own away timeout |

#### Server → Client

| Event | Payload | Description |
|-------|---------|-------------|
| `sendUserStatus` | `string` | Your own current status |
| `sendOnlineUser` | `JSON {"online":["uid1"],"away":["uid2"],"inMeeting":["uid3"]}` | Full categorized online user list |
| `sendUserTimeAway` | `number` | Your away timeout in minutes |
| `sendAwayTime` | `number` | Same as above (via `sendToAllSockets`) |
| `mercure` | `JSON string` | Mercure-published message from PHP backend |
| `openNewIframe` | `JSON { url, title }` | Command to open a new iframe |
| `giveOnlineStatus` | `JSON { "uid1": "online", "uid2": "offline" }` | Status for requested user IDs |

---

## How to Test

### Automated Tests (Node.js)

#### Prerequisites

```bash
cd nodejs
npm install
```

#### Run Standalone Tests (no Redis needed)

```bash
npm test
```

This runs **12 tests** in `tests/websocket.test.mjs`. These tests spin up an
in-process Socket.IO server and verify:

- JWT authentication (valid and invalid tokens)
- Online user list generation and propagation
- User disconnect and offline detection (after 7s timer)
- Status changes (`setStatus`, `enterMeeting`, `leaveMeeting`)
- `getMyStatus` and `getStatusForListOfIds`
- Multi-tab scenarios (same userId, multiple sockets, partial disconnect)

```
  WebSocket Server
    Connection
      ✔ should reject connections without a token
      ✔ should accept connections with a valid JWT
    Online Status
      ✔ should emit online user list containing both users on connect
      ✔ should remove disconnected user from online list
      ✔ should change user status via setStatus event
    In-Meeting Status
      ✔ should reflect inMeeting status after enterMeeting
      ✔ should revert from inMeeting after leaveMeeting
    getMyStatus
      ✔ should return the user's own status
    getStatusForListOfIds
      ✔ should return status for requested user IDs
      ✔ should return offline for unknown user IDs
    Multi-Tab
      ✔ should keep user online when one tab disconnects but another stays
      ✔ should go offline when all tabs disconnect
```

#### Run Cluster Tests (requires Redis)

```bash
npm run test:cluster
```

These **8 tests** in `tests/websocket-cluster.test.mjs` require Redis. The test helper
(`tests/helpers/redis-helper.mjs`) auto-detects Redis availability:

1. Tries `REDIS_URL` env var
2. Tries default `localhost:6379`
3. Tries DDEV Redis container (`ddev-jitsi-admin-redis`)
4. Spins up a temporary `redis:7-alpine` Docker container on port 16379 if none found

```
  WebSocket Cluster (Active/Active)
    Cross-Instance User Presence
      ✔ should see user on Instance A from Instance B
      ✔ should NOT see user from A on B after disconnect + 7s delay
    Cross-Instance Status Propagation
      ✔ should reflect status change from A on B
    Cross-Instance Room Broadcast (io.to)
      ✔ should deliver io.to(room).emit across instances
    Redis State Persistence
      ✔ should store and retrieve user in Redis hash
      ✔ should increment socketsCount for multi-tab on same instance
      ✔ should decrement socketsCount on single tab disconnect
    Heartbeat
      ✔ should update updatedAt timestamp periodically
```

#### Run All Tests

```bash
npm run test:all
```

---

### E2E Test Script (curl + DDEV)

The script `nodejs/tests/e2e-test.sh` runs a full end-to-end test against a running
WebSocket server. It auto-detects whether it's running inside DDEV or on the host.

#### Running Inside DDEV

```bash
ddev exec bash nodejs/tests/e2e-test.sh
```

#### Running from Host (DDEV Running)

```bash
bash nodejs/tests/e2e-test.sh
```

#### What It Tests

| # | Test | Method |
|---|------|--------|
| 1 | Health check | `curl GET /healthz` — expects HTTP 200 |
| 2 | Server alive | `curl GET /` — expects HTTP 404 (no root route) |
| 3 | Mercure hub liveness | `curl GET /.well-known/mercure` — expects HTTP 200 |
| 4 | Redis connectivity | `redis-cli PING` — expects `PONG` |
| 5 | Mercure publish (valid JWT) | `curl POST` with Bearer JWT — expects HTTP 200 |
| 6 | Mercure publish (invalid JWT) | `curl POST` with invalid JWT — expects HTTP 403 |
| 7 | WebSocket client connection | `socket.io-client` script — expects `sendUserStatus` |
| 8 | Redis user state | `redis-cli HLEN users` — checks hash is accessible |

---

### Manual Testing via Browser

#### Step 1: Ensure the Server is Running

With DDEV:
```bash
ddev start
```

The WebSocket server is available at `https://jitsi-admin.ddev.site:3000`.

#### Step 2: Generate a JWT Token

Open the browser developer console on any jitsi-admin page and run:

```javascript
// The JWT is already available as a global variable on most pages
console.log(websocketTopics);
```

Or generate one manually using the PHP Symfony console:

```bash
ddev exec php bin/console
```

Alternatively, from the Node.js directory:
```bash
cd nodejs
node -e "
const jwt = require('jsonwebtoken');
const token = jwt.sign(
  { iss:'jitsi-admin', aud:'jitsi-admin', sub:'manual-test-user', status:1, rooms:[], iat:Math.floor(Date.now()/1000), nbf:Math.floor(Date.now()/1000), exp:Math.floor(Date.now()/1000)+86400 },
  'MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5',
  { algorithm:'HS256' }
);
console.log(token);
"
```

#### Step 3: Connect via Browser Console

On any jitsi-admin page, open the browser console and run:

```javascript
// The page already has socket.io-client loaded via the app's websocket.js
// Check the current connection:
const socket = window.socket || (typeof socket !== 'undefined' ? socket : null);

// If socket is defined, test status:
if (typeof sendViaWebsocket !== 'undefined') {
  // Request your own status
  sendViaWebsocket('getMyStatus');

  // Request full online list
  sendViaWebsocket('getStatus');

  // Set your status to 'away'
  sendViaWebsocket('setStatus', 'away');

  // Check specific user IDs:
  sendViaWebsocket('giveOnlineStatus', '["some-user-uid"]');
}

// Listen for events:
socket.on('sendOnlineUser', data => console.log('Online users:', JSON.parse(data)));
socket.on('sendUserStatus', status => console.log('My status:', status));
```

#### Step 4: Verify Online Status in the UI

1. Log in to jitsi-admin
2. Look at the user profile/status indicator (top right)
3. Open the address book — user status bubbles should show online/away/offline/busy
4. Open the dashboard — user avatars should show status indicators

#### Step 5: Test Real-Time Notifications

1. Open two browser tabs, both logged into jitsi-admin
2. In Tab 1, join a lobby waiting room
3. In Tab 2 (moderator view), accept the waiting user
4. Tab 1 should receive a snackbar notification and redirect

---

### Manual Testing via curl

#### Health Check

```bash
curl -sk https://jitsi-admin.ddev.site:3000/healthz
# Expected: (empty response, HTTP 200)
```

If running directly without DDEV:
```bash
curl http://localhost:3000/healthz
```

#### Mercure Hub Liveness

```bash
curl -sk https://jitsi-admin.ddev.site:3000/.well-known/mercure
# Expected: (empty response, HTTP 200)
```

#### Publish a Test Message

First generate a publish JWT:
```bash
PUB_JWT=$(node -e "
  const jwt = require('jsonwebtoken');
  const token = jwt.sign(
    { iss: 'jitsi-admin', aud: 'jitsi-admin', publish: ['*'] },
    'MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5',
    { algorithm: 'HS256' }
  );
  console.log(token);
")
```

Then publish:
```bash
curl -sk -X POST https://jitsi-admin.ddev.site:3000/.well-known/mercure \
  -H "Authorization: Bearer $PUB_JWT" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'topic=test-room&data={"type":"snackbar","message":"Hello World","color":"success"}'
# Expected: OK (HTTP 200)
```

#### Publish with Wrong Secret (Should Fail)

```bash
curl -sk -X POST https://jitsi-admin.ddev.site:3000/.well-known/mercure \
  -H "Authorization: Bearer invalid-jwt-token" \
  -H "Content-Type: application/x-www-form-urlencoded" \
  -d 'topic=test&data=test'
# Expected: HTTP 403
```

#### Verify Redis User State

```bash
# Inside DDEV:
ddev exec -s redis redis-cli HLEN users
# Expected: number of currently connected users

# Check a specific user:
ddev exec -s redis redis-cli HGET users "[user-uid]"

# List all users:
ddev exec -s redis redis-cli HGETALL users
```

#### Test WebSocket Connection via wscat

```bash
# Install wscat if needed: npm install -g wscat

# Generate a client JWT first:
CLIENT_JWT=$(node -e "
  const jwt = require('jsonwebtoken');
  const token = jwt.sign(
    { iss:'jitsi-admin', aud:'jitsi-admin', sub:'curl-test-user', status:1, rooms:[], iat:Math.floor(Date.now()/1000), nbf:Math.floor(Date.now()/1000), exp:Math.floor(Date.now()/1000)+86400 },
    'MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5',
    { algorithm:'HS256' }
  );
  console.log(token);
")

# Connect (Socket.IO requires path and query params):
# Note: wscat doesn't support Socket.IO protocol directly.
# Use the node -e one-liner from the e2e-test.sh script instead.
```

#### Quick Socket.IO Connection Test (Node.js One-Liner)

```bash
cd nodejs
node -e "
  const { io } = require('socket.io-client');
  const jwt = require('jsonwebtoken');
  const token = jwt.sign(
    { iss:'jitsi-admin', aud:'jitsi-admin', sub:'quick-test', status:1, rooms:[], iat:Math.floor(Date.now()/1000), nbf:Math.floor(Date.now()/1000), exp:Math.floor(Date.now()/1000)+86400 },
    'MDY3OTljNDM3MzRjMWU4ZmFkZTFlNzY5',
    { algorithm:'HS256' }
  );
  const socket = io('https://jitsi-admin.ddev.site:3000', {
    path: '/ws',
    query: { token },
    transports: ['websocket'],
    rejectUnauthorized: false,
  });
  socket.on('connect', () => console.log('Connected!'));
  socket.on('sendUserStatus', s => console.log('Status:', s));
  socket.on('sendOnlineUser', d => console.log('Online:', JSON.parse(d)));
  socket.on('connect_error', e => console.log('Error:', e.message));
  setTimeout(() => { socket.close(); process.exit(0); }, 5000);
"
```

---

## Troubleshooting

### Server fails to start

```bash
# Check logs
ddev logs -s websocket

# Check if port 3000 is in use
fuser 3000/tcp
```

### "Authentication error" on client connect

- Ensure `WEBSOCKET_SECRET` matches between `.env` (PHP) and the Node.js env
- Check that the browser's JWT is valid (not expired — JWTs expire after 3 days)
- Verify the JWT includes `sub` field

### "Redis not available" warning

This is not an error — the server falls back to standalone mode.
To enable Redis clustering:
- Ensure Redis container is running: `ddev exec -s redis redis-cli PING`
- Set `REDIS_ENABLED=true` in the websocket container environment

### Users not appearing in cross-instance online list

- Verify Redis adapter initialized: check server logs for `🔗 Redis-Adapter aktiviert`
- Check that `REDIS_ENABLED=true` on ALL instances
- Verify all instances connect to the same Redis server (same host:port)
- Run `redis-cli HGETALL users` to see if user data is being written

### Test failures

**Standalone tests (npm test)**
- Port 3099 must be free — check with `fuser 3099/tcp`
- `WEBSOCKET_SECRET` env var must be set (the npm script handles this)

**Cluster tests (npm run test:cluster)**
- Requires Docker (for auto-spinning Redis) or a running Redis instance
- Docker must be running: `docker ps`
- Ports 3100, 3101, and 16379 (for Docker Redis) must be free

**E2E test (e2e-test.sh)**
- DDEV must be running: `ddev status`
- `node` and `curl` must be available
- Redis container must be reachable

---

## DDEV Development Setup

The DDEV configuration files are in `.ddev/`:

| File | Purpose |
|------|---------|
| `docker-compose.redis.yaml` | Redis 7 Alpine container, linked to web service |
| `docker-compose.websocket.yaml` | Builds `nodejs/`, exposes port 3000, links to Redis, sets `REDIS_ENABLED=true` |
| `.env.web` | Sets `MERCURE_URL`, `MERCURE_PUBLIC_URL`, `MERCURE_JWT_SECRET` |

### Starting DDEV with WebSocket + Redis

```bash
ddev start
```

This starts all services: web, db, keycloak, redis, and websocket.

### Viewing Logs

```bash
# WebSocket server logs
ddev logs -s websocket

# Redis logs
ddev logs -s redis

# PHP (web) logs
ddev logs -s web
```

### Restarting the WebSocket Service

```bash
ddev restart -s websocket
```

### Running Tests Inside DDEV

```bash
# Install dependencies first (only needed once)
ddev exec -s websocket npm install

# Standalone tests
ddev exec -s websocket npm test

# Cluster tests (use Docker on host for Redis auto-detection)
npm run test:cluster
```

For cluster tests to work inside DDEV, the Redis helper will use the DDEV Redis
container automatically (it checks `ddev-jitsi-admin-redis:6379`).


---

## Installation & Deployment

### Bare-Metal (install.sh)

The `install.sh` script deploys the WebSocket service automatically:

1. Runs `npm install` in `nodejs/` (line 83)
2. Copies the entire `nodejs/` directory to `/usr/local/bin/websocket` (line 109)
3. Copies the systemd unit: `installer/jitsi-admin_websocket.service` to `/etc/systemd/system/` (line 110)
4. Creates `/var/log/websocket/` for log output (line 111)
5. Enables and starts the service (lines 127-130)

The service runs as `www-data` user. Configuration comes from environment variables
in `/etc/systemd/system/jitsi-admin_websocket.environment`. The installer does **not**
create this file — it must be populated manually with `WEBSOCKET_SECRET`, `REDIS_ENABLED`,
`REDIS_HOST`, and `REDIS_PORT`.

**The systemd unit is designed for single-instance only.** For active/active clustering
on bare-metal, you must create multiple unit files with different ports (`PORT=3001`,
`PORT=3002`, etc.) and ensure `REDIS_ENABLED=true` on all of them.

### Systemd Unit Files

Two different service files exist in the repository:

| File | Environment File | Used By |
|------|-----------------|---------|
| `nodejs/config/websocket.service` | `/etc/systemd/system/jitsi-admin.conf` | Not used by installer — reference only |
| `installer/jitsi-admin_websocket.service` | `/etc/systemd/system/jitsi-admin_websocket.environment` | Deployed by `install.sh` |

The service uses `Type=simple`, runs as `www-data`, and logs to `/var/log/websocket/`.
No systemd hardening directives (`ProtectSystem`, `PrivateTmp`, `NoNewPrivileges`)
are present.

### Docker (installDocker.sh)

The Docker installer auto-generates all Mercure/WebSocket secrets:

- Generates `MERCURE_JWT_SECRET` as a random hex string
- Sets `MERCURE_URL=http://websocket-ja:3000/.well-known/mercure`
- Sets `MERCURE_PUBLIC_URL=${HTTP_METHOD}://${PUBLIC_URL}`
- Sets `WEBSOCKET_SECRET=${MERCURE_JWT_SECRET}`

The websocket service is built from `nodejs/` via `docker-compose.yml` or pulled
as a pre-built image from Docker Hub (`h2invent/jitsi-admin-websocket:latest`).

### Docker Image Variants

| Dockerfile | Base Image | Features | Used In |
|-----------|-----------|----------|---------|
| `nodejs/Dockerfile` | `node:20-alpine` | No HEALTHCHECK, runs as `node` user | Dev builds, Docker Hub |
| `nodejs/Dockerfile-prod` | `node:23-alpine` | HEALTHCHECK via curl, labels, runs as `node` user | Production, Harbor registry |

### CI/CD Pipeline

The WebSocket Docker image is built in CI pipelines:

| Pipeline | Dockerfile | Registry |
|----------|-----------|----------|
| `pipeline-development.yml` | `./nodejs/Dockerfile-prod` | `h2invent/jitsi-admin-websocket:dev` |
| `pipeline-release.yml` | `./nodejs/Dockerfile` | `h2invent/jitsi-admin-websocket` (Docker Hub) |
| `pipeline-release.yml` | `./nodejs/Dockerfile-prod` | `reg.h2-invent.com/meetling/websocket` (Harbor) |

Tags include `latest`, semver, and environment-specific tags (`dev`, `staging`).

**Known gap**: The CI pipelines do **not** run the Node.js test suite. Only PHP
unit tests (`composer test`) are executed. To run WebSocket tests in CI, add a job:

```yaml
- name: Run WebSocket tests
  run: |
    cd nodejs
    npm ci
    npm test
```

Similarly, vulnerability scanning (`vulnerability.yml`) scans only `composer.lock`
for PHP dependencies, not `nodejs/package-lock.json` for Node.js dependencies.

### docker-compose Files

| File | WebSocket Service | Notes |
|------|-------------------|-------|
| `docker-compose.yml` | `websocket-ja` | Single instance, sticky sessions on HTTP app but NOT on websocket service |
| `docker-compose.cluster.yml` | `websocket-ja` | Uses `RANDOMTAG` for namespace isolation, assumes external Traefik on `gateway` network |
| `docker-compose.test.yml` | `websocket-ja` | Test environment, includes Traefik routing at `/ws` path prefix |
| `.ddev/docker-compose.websocket.yaml` | `websocket` | DDEV development, `REDIS_ENABLED=true` pre-configured |

In the cluster compose file, Traefik is assumed to be running externally (on a
`gateway` network) and the websocket service registers itself via labels:

```yaml
labels:
  - "traefik.http.routers.websocket-ja-${RANDOMTAG}.rule=Host(`${PUBLIC_URL}`)&& PathPrefix(`/ws`)"
  - "traefik.http.services.websocket-ja-${RANDOMTAG}.loadbalancer.server.port=3000"
```
