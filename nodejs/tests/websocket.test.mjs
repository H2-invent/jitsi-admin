import { createServer } from "http";
import jwtLib from "jsonwebtoken";
import { io as ClientIO } from "socket.io-client";
import { expect } from "chai";

const WEBSOCKET_SECRET = "test-secret-do-not-use-in-production";
const TEST_PORT = 3099;
const SERVER_URL = `http://localhost:${TEST_PORT}`;

let ioLocal;
let httpServer;

function makeToken(payload = {}) {
  return jwtLib.sign(
    {
      iss: "jitsi-admin",
      aud: "jitsi-admin",
      sub: payload.sub || "test-user-1",
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

function connectClient(payload = {}) {
  return ClientIO(`${SERVER_URL}`, {
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

async function once(socket, event, timeoutMs = 5000) {
  return new Promise((resolve, reject) => {
    const timer = setTimeout(() => reject(new Error(`Event "${event}" timed out after ${timeoutMs}ms`)), timeoutMs);
    socket.once(event, (data) => {
      clearTimeout(timer);
      resolve(data);
    });
  });
}

describe("WebSocket Server", function () {
  this.timeout(30000);

  before(async function () {
    const { Server } = await import("socket.io");
    const { setIO } = await import("../ioRegistry.mjs");
    const { websocketState } = await import("../websocketState.mjs");
    const { getOnlineUser, loginUser } = await import("../login.mjs");

    httpServer = createServer();
    ioLocal = new Server(httpServer, {
      path: "/ws",
      cors: { origin: "*", methods: ["GET", "POST"] },
    });
    setIO(ioLocal);

    ioLocal.use((socket, next) => {
      if (socket.handshake.query?.token) {
        jwtLib.verify(socket.handshake.query.token, WEBSOCKET_SECRET, (err, decoded) => {
          if (err) return next(new Error("Authentication error"));
          socket.decoded = decoded;
          next();
        });
      } else {
        next(new Error("Authentication error"));
      }
    });

    ioLocal.on("connection", async (socket) => {
      const jwtObj = jwtLib.decode(socket.handshake.query.token);
      if (jwtObj?.rooms) jwtObj.rooms.forEach((room) => socket.join(room));

      const user = await loginUser(socket);
      if (user) {
        user.initUserAway?.();
        socket.emit("sendUserStatus", user.getStatus?.());
        socket.emit("sendUserTimeAway", user.awayTime ?? 0);
        ioLocal.emit("sendOnlineUser", JSON.stringify(await getOnlineUser()));
      }

      socket.on("disconnect", () => websocketState("disconnect", socket, null));
      socket.onAny((event, data) => websocketState(event, socket, data));
    });

    await new Promise((resolve) => httpServer.listen(TEST_PORT, resolve));
  });

  after(function () {
    ioLocal?.close();
    httpServer?.close();
  });

  describe("Connection", function () {
    it("should reject connections without a token", async function () {
      const client = ClientIO(`${SERVER_URL}`, {
        path: "/ws",
        transports: ["websocket"],
        timeout: 3000,
        reconnection: false,
        query: {},
      });
      await new Promise((resolve) => {
        client.on("connect_error", (err) => {
          expect(err.message).to.equal("Authentication error");
          client.close();
          resolve();
        });
      });
    });

    it("should accept connections with a valid JWT", async function () {
      const client = connectClient({ sub: "conn-test-user" });
      const status = await once(client, "sendUserStatus");
      expect(["online", "offline"]).to.include(status);
      client.close();
    });
  });

  describe("Online Status", function () {
    it("should emit online user list containing both users on connect", async function () {
      const client1 = connectClient({ sub: "os-user-a" });
      await once(client1, "sendUserStatus");
      await sleep(300);

      const client2 = connectClient({ sub: "os-user-b" });
      await once(client2, "sendUserStatus");
      await sleep(300);

      // Request online list explicitly via getStatus (io.emit)
      client1.emit("getStatus");
      const listData = await once(client1, "sendOnlineUser");
      const list = JSON.parse(listData);
      const allIds = Object.values(list).flat();
      expect(allIds).to.include("os-user-a");
      expect(allIds).to.include("os-user-b");

      client1.close();
      client2.close();
    });

    it("should remove disconnected user from online list", async function () {
      const client = connectClient({ sub: "dc-user-c" });
      const initialStatus = await once(client, "sendUserStatus");
      expect(initialStatus).to.equal("online");
      await sleep(300);

      client.close();
      // Wait for 7s disconnect timer in websocketState
      await sleep(8000);

      // Connect a new client and request fresh online list
      const observer = connectClient({ sub: "dc-observer" });
      await once(observer, "sendUserStatus");
      await sleep(300);
      observer.emit("getStatus");
      const onlineList = await once(observer, "sendOnlineUser", 5000);
      const list = JSON.parse(onlineList);
      // User should be in 'offline' category, not in online/away/inMeeting
      const activeIds = [...(list["online"] || []), ...(list["away"] || []), ...(list["inMeeting"] || [])];
      expect(activeIds).to.not.include("dc-user-c");

      observer.close();
    });

    it("should change user status via setStatus event", async function () {
      const client = connectClient({ sub: "status-change", status: 1 });
      await once(client, "sendUserStatus");
      await sleep(300);

      client.emit("setStatus", "away");
      const statusEvent = await once(client, "sendUserStatus");
      expect(statusEvent).to.equal("away");

      client.close();
    });
  });

  describe("In-Meeting Status", function () {
    it("should reflect inMeeting status after enterMeeting", async function () {
      const client = connectClient({ sub: "meet-user-1", status: 1 });
      await once(client, "sendUserStatus");
      await sleep(200);

      client.emit("enterMeeting");
      await once(client, "sendUserStatus");
      await sleep(200);

      // Request online list explicitly
      client.emit("getStatus");
      const onlineData = await once(client, "sendOnlineUser");
      const list = JSON.parse(onlineData);

      expect(list["inMeeting"]).to.include("meet-user-1");

      client.close();
    });

    it("should revert from inMeeting after leaveMeeting", async function () {
      const client = connectClient({ sub: "meet-user-2", status: 1 });
      await once(client, "sendUserStatus");
      await sleep(200);

      client.emit("enterMeeting");
      await once(client, "sendUserStatus");
      await sleep(200);

      client.emit("leaveMeeting");
      await once(client, "sendUserStatus");
      await sleep(200);

      client.emit("getStatus");
      const onlineData = await once(client, "sendOnlineUser");
      const list = JSON.parse(onlineData);

      expect(list["inMeeting"] || []).to.not.include("meet-user-2");

      client.close();
    });
  });

  describe("getMyStatus", function () {
    it("should return the user's own status", async function () {
      const client = connectClient({ sub: "mystatus-user", status: 1 });
      await once(client, "sendUserStatus");

      client.emit("getMyStatus");
      const result = await once(client, "sendUserStatus");
      expect(["online", "offline"]).to.include(result);

      client.close();
    });
  });

  describe("getStatusForListOfIds", function () {
    it("should return status for requested user IDs", async function () {
      const client1 = connectClient({ sub: "list-1", status: 1 });
      await once(client1, "sendUserStatus");
      await sleep(200);

      const client2 = connectClient({ sub: "list-2", status: 0 });
      await once(client2, "sendUserStatus");
      await sleep(200);

      client2.emit("giveOnlineStatus", JSON.stringify(["list-1", "list-2"]));
      const result = await once(client2, "giveOnlineStatus");
      const statuses = JSON.parse(result);

      expect(statuses).to.have.property("list-1");
      expect(statuses).to.have.property("list-2");

      client1.close();
      client2.close();
    });

    it("should return offline for unknown user IDs", async function () {
      const client = connectClient({ sub: "list-checker-2" });
      await once(client, "sendUserStatus");
      await sleep(200);

      client.emit("giveOnlineStatus", JSON.stringify(["no-such-user"]));
      const result = await once(client, "giveOnlineStatus");
      const statuses = JSON.parse(result);

      expect(statuses["no-such-user"]).to.equal("offline");

      client.close();
    });
  });

  describe("Multi-Tab (same userId, multiple sockets)", function () {
    it("should keep user online when one tab disconnects but another stays", async function () {
      const token = makeToken({ sub: "mt-keep", status: 1 });

      const tab1 = ClientIO(SERVER_URL, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      const tab2 = ClientIO(SERVER_URL, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });

      await Promise.all([once(tab1, "sendUserStatus"), once(tab2, "sendUserStatus")]);
      await sleep(500);

      tab1.close();
      await sleep(8000);

      // Use tab2's own getStatus to check if it still exists
      tab2.emit("getStatus");
      const onlineData = await once(tab2, "sendOnlineUser");
      const list = JSON.parse(onlineData);
      const allIds = Object.values(list).flat();

      expect(allIds).to.include("mt-keep");

      tab2.close();
    });

    it("should go offline when all tabs disconnect", async function () {
      const token = makeToken({ sub: "mt-all-off", status: 1 });

      const tab1 = ClientIO(SERVER_URL, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });
      const tab2 = ClientIO(SERVER_URL, {
        path: "/ws", query: { token }, transports: ["websocket"], timeout: 5000, reconnection: false,
      });

      await Promise.all([once(tab1, "sendUserStatus"), once(tab2, "sendUserStatus")]);
      await sleep(500);

      tab1.close();
      tab2.close();
      await sleep(8000);

      const observer = connectClient({ sub: "mt-obs-all-off" });
      await once(observer, "sendUserStatus");
      await sleep(300);
      observer.emit("getStatus");
      const onlineData = await once(observer, "sendOnlineUser", 5000);
      const list = JSON.parse(onlineData);
      const activeIds = [...(list["online"] || []), ...(list["away"] || []), ...(list["inMeeting"] || [])];
      expect(activeIds).to.not.include("mt-all-off");

      observer.close();
    });
  });
});
