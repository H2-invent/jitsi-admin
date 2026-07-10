import { createServer } from "http";
import jwtLib from "jsonwebtoken";
import { io as ClientIO } from "socket.io-client";
import { Server } from "socket.io";
import { expect } from "chai";
import { startRedis, cleanupRedis } from "./helpers/redis-helper.mjs";
import { setIO } from "../ioRegistry.mjs";

const WEBSOCKET_SECRET = "test-cluster-secret-do-not-use-in-production";
const PORT_A = 3100;
const PORT_B = 3101;

let redisClient = null;
let serverA, serverB;
let ioA, ioB;
let clearIntervalA, clearIntervalB;

function makeToken(payload = {}) {
  return jwtLib.sign(
    {
      iss: "jitsi-admin",
      aud: "jitsi-admin",
      sub: payload.sub || "cluster-test-user",
      status: payload.status ?? 1,
      rooms: payload.rooms || [],
      iat: Math.floor(Date.now() / 1000),
      nbf: Math.floor(Date.now() / 1000),
      exp: Math.floor(Date.now() / 1000) + 86400,
    },
    WEBSOCKET_SECRET,
    { algorithm: "HS256" }
  );
}

function connectTo(port, payload = {}) {
  return ClientIO(`http://localhost:${port}`, {
    path: "/ws",
    query: { token: makeToken(payload) },
    transports: ["websocket"],
    timeout: 5000,
    reconnection: false,
  });
}

async function sleep(ms) {
  return new Promise((resolve) => setTimeout(resolve, ms));
}

async function once(socket, event, timeoutMs = 8000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(`Event "${event}" timed out after ${timeoutMs}ms`)), timeoutMs);
    socket.once(event, (data) => {
      clearTimeout(timer);
      resolve(data);
    });
  });
}

async function createServerInstance(port, adapter) {
  const httpServer = createServer();
  const io = new Server(httpServer, {
    path: "/ws",
    cors: { origin: "*", methods: ["GET", "POST"] },
  });

  if (adapter) {
    io.adapter(adapter);
  }

  io.use((socket, next) => {
    if (socket.handshake.query?.token) {
      jwtLib.verify(socket.handshake.query.token, WEBSOCKET_SECRET, (err, decoded) => {
        if (err) return next(new Error("Authentication error"));
        socket.decoded = decoded;
        next();
      });
    } else next(new Error("Authentication error"));
  });

  io.on("connection", async (socket) => {
    const { websocketState } = await import("../websocketState.mjs");
    const { loginUser, getOnlineUSer } = await import("../login.mjs");
    const jwtObj = jwtLib.decode(socket.handshake.query.token);
    if (jwtObj?.rooms) jwtObj.rooms.forEach((room) => socket.join(room));

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

  // Start heartbeat to keep user data fresh in Redis (every 10s)
  const { getUserId } = await import("../login.mjs");
  const heartbeatInterval = setInterval(async () => {
    try {
      for (const socket of io.sockets.sockets.values()) {
        const userId = getUserId(socket);
        if (!userId || !redisClient) continue;
        const existing = await redisClient.hGet("users", userId);
        let existingData = {};
        try { existingData = existing ? JSON.parse(existing) : {}; } catch {}
        await redisClient.hSet("users", userId, JSON.stringify({
          ...existingData,
          id: userId,
          status: existingData.status || "online",
          socketsCount: existingData.socketsCount || 1,
          inMeetingCount: existingData.inMeetingCount || 0,
          away: existingData.away || false,
          awayTime: existingData.awayTime || 5,
          updatedAt: Date.now()
        }));
      }
    } catch (err) {}
  }, 10000);

  await new Promise((resolve) => httpServer.listen(port, resolve));
  return { httpServer, io, heartbeatInterval };
}

describe("WebSocket Cluster (Active/Active)", function () {
  this.timeout(60000);

  before(async function () {
    // Set env vars for login.mjs to use Redis
    process.env.REDIS_ENABLED = "true";
    process.env.WEBSOCKET_SECRET = WEBSOCKET_SECRET;

    const result = await startRedis();
    if (!result) {
      this.skip(); // Skip all cluster tests — no Redis available
      return;
    }
    redisClient = result.redisClient;
    process.env.REDIS_HOST = result.redisClient.options?.socket?.host || "localhost";
    process.env.REDIS_PORT = String(result.redisClient.options?.socket?.port || 6379);

    // Flush test data
    await redisClient.flushAll();

    // Create Redis adapter
    const { createAdapter } = await import("@socket.io/redis-adapter");
    const { createClient } = await import("redis");
    const adapterPub = createClient({ url: `redis://${process.env.REDIS_HOST}:${process.env.REDIS_PORT}` });
    const adapterSub = adapterPub.duplicate();
    await adapterPub.connect();
    await adapterSub.connect();
    const adapter = createAdapter(adapterPub, adapterSub);

    // Create two server instances sharing the same Redis
    const a = await createServerInstance(PORT_A, adapter);
    const b = await createServerInstance(PORT_B, adapter);
    serverA = a.httpServer;
    serverB = b.httpServer;
    ioA = a.io;
    ioB = b.io;
    clearIntervalA = a.heartbeatInterval;
    clearIntervalB = b.heartbeatInterval;

    // Set io registry for User.mjs (both instances must use their local io)
    setIO(ioA);
  });

  after(async function () {
    clearInterval(clearIntervalA);
    clearInterval(clearIntervalB);
    ioA?.close();
    ioB?.close();
    serverA?.close();
    serverB?.close();
    try { await redisClient?.flushAll(); } catch {}
    await cleanupRedis();
  });

  describe("Cross-Instance User Presence", function () {
    it("should see user on Instance A from Instance B", async function () {
      const userA = connectTo(PORT_A, { sub: "cross-presence-user", status: 1 });
      await once(userA, "sendUserStatus");
      await sleep(500);

      // Observer on Instance B should see the user
      setIO(ioB);
      const observerB = connectTo(PORT_B, { sub: "cross-observer" });
      await once(observerB, "sendUserStatus");
      await sleep(500);

      observerB.emit("getStatus");
      const listData = await once(observerB, "sendOnlineUser");
      const list = JSON.parse(listData);
      const allIds = Object.values(list).flat();
      expect(allIds).to.include("cross-presence-user");

      // Restore ioA for cleanup
      setIO(ioA);
      userA.close();
      observerB.close();
    });

    it("should NOT see user from A on B after disconnect + 7s delay", async function () {
      const userA = connectTo(PORT_A, { sub: "cross-disconnect", status: 1 });
      await once(userA, "sendUserStatus");
      await sleep(500);

      userA.close();
      // Wait for 7s disconnect timer
      await sleep(8000);

      setIO(ioB);
      const observerB = connectTo(PORT_B, { sub: "cross-obs-after-dc" });
      await once(observerB, "sendUserStatus");
      await sleep(500);

      observerB.emit("getStatus");
      const listData = await once(observerB, "sendOnlineUser");
      const list = JSON.parse(listData);
      const activeIds = [...(list["online"] || []), ...(list["away"] || []), ...(list["inMeeting"] || [])];
      expect(activeIds).to.not.include("cross-disconnect");

      setIO(ioA);
      observerB.close();
    });
  });

  describe("Cross-Instance Status Propagation", function () {
    it("should reflect status change from A on B", async function () {
      const userA = connectTo(PORT_A, { sub: "cross-status", status: 1 });
      await once(userA, "sendUserStatus");
      await sleep(500);

      // Observer on B
      setIO(ioB);
      const observerB = connectTo(PORT_B, { sub: "cross-status-obs" });
      await once(observerB, "sendUserStatus");
      await sleep(500);

      // Change status on A
      setIO(ioA);
      userA.emit("setStatus", "away");
      await once(userA, "sendUserStatus");
      await sleep(800);

      // B should see the updated status
      setIO(ioB);
      observerB.emit("getStatus");
      const listData = await once(observerB, "sendOnlineUser");
      const list = JSON.parse(listData);
      expect(list["away"] || []).to.include("cross-status");

      setIO(ioA);
      userA.close();
      observerB.close();
    });
  });

  describe("Cross-Instance Room Broadcast (io.to)", function () {
    it("should deliver io.to(room).emit across instances", function (done) {
      const roomName = "test-room-" + Date.now();

      const userA = connectTo(PORT_A, { sub: "room-user-a", status: 1, rooms: [roomName] });
      const userB = connectTo(PORT_B, { sub: "room-user-b", status: 1, rooms: [roomName] });

      let receivedA = false;
      let receivedB = false;

      userA.on("mercure", (data) => {
        const parsed = JSON.parse(data);
        if (parsed.testId === roomName) receivedA = true;
        checkDone();
      });
      userB.on("mercure", (data) => {
        const parsed = JSON.parse(data);
        if (parsed.testId === roomName) receivedB = true;
        checkDone();
      });

      function checkDone() {
        if (receivedA && receivedB) {
          userA.close();
          userB.close();
          done();
        }
      }

      Promise.all([once(userA, "sendUserStatus"), once(userB, "sendUserStatus")]).then(() => {
        // io.to(room).emit on Instance A — should reach user on Instance B via adapter
        ioA.to(roomName).emit("mercure", JSON.stringify({ type: "test", testId: roomName }));
      });

      setTimeout(() => {
        if (!receivedA || !receivedB) {
          userA.close();
          userB.close();
          done(new Error(`Room broadcast failed: A=${receivedA}, B=${receivedB}`));
        }
      }, 5000);
    });
  });

  describe("Redis State Persistence", function () {
    it("should store and retrieve user in Redis hash", async function () {
      const user = connectTo(PORT_A, { sub: "redis-state", status: 1 });
      await once(user, "sendUserStatus");
      await sleep(500);

      const raw = await redisClient.hGet("users", "redis-state");
      expect(raw).to.not.be.null;
      const data = JSON.parse(raw);
      expect(data.id).to.equal("redis-state");
      expect(data.socketsCount).to.be.at.least(1);

      user.close();
    });

    it("should increment socketsCount for multi-tab on same instance", async function () {
      const token = makeToken({ sub: "redis-multitab", status: 1 });

      const tab1 = connectTo(PORT_A);
      // Override the token for same userId
      tab1.io.opts.query = { token };
      tab1.disconnect();
      const tab1Real = ClientIO(`http://localhost:${PORT_A}`, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      await once(tab1Real, "sendUserStatus");

      const tab2Real = ClientIO(`http://localhost:${PORT_A}`, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      await once(tab2Real, "sendUserStatus");
      await sleep(500);

      const raw = await redisClient.hGet("users", "redis-multitab");
      const data = JSON.parse(raw);
      expect(data.socketsCount).to.equal(2);

      tab1Real.close();
      tab2Real.close();
    });

    it("should decrement socketsCount on single tab disconnect", async function () {
      const token = makeToken({ sub: "redis-countdown", status: 1 });

      const tab1 = ClientIO(`http://localhost:${PORT_A}`, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      await once(tab1, "sendUserStatus");

      const tab2 = ClientIO(`http://localhost:${PORT_A}`, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      await once(tab2, "sendUserStatus");
      await sleep(500);

      tab1.close();
      await sleep(1000);

      const raw = await redisClient.hGet("users", "redis-countdown");
      const data = JSON.parse(raw);
      expect(data.socketsCount).to.equal(1);

      tab2.close();
    });
  });

  describe("Heartbeat", function () {
    it("should update updatedAt timestamp periodically", async function () {
      const user = connectTo(PORT_A, { sub: "redis-heartbeat", status: 1 });
      await once(user, "sendUserStatus");

      const raw1 = await redisClient.hGet("users", "redis-heartbeat");
      const ts1 = JSON.parse(raw1).updatedAt;

      // Heartbeat runs every 10s — wait 12s
      await sleep(12000);

      const raw2 = await redisClient.hGet("users", "redis-heartbeat");
      expect(raw2).to.not.be.null;
      const ts2 = JSON.parse(raw2).updatedAt;
      expect(ts2).to.be.greaterThan(ts1);

      user.close();
    });
  });
});
