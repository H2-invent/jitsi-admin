import express from "express";
import bodyParser from "body-parser";
import http from "http";
import https from "https";
import fs from "fs";
import jwt from "jsonwebtoken";
import { Server } from "socket.io";

import { setupRedisAdapter } from "./redisAdapter.mjs";
import { getOnlineUsers } from "./redisState.mjs";
import { loginUser, disconnectUser } from "./login.mjs";
import { checkFileContains } from "./checkCertAndKey.js";
import {
    PORT,
    WEBSOCKET_SECRET,
    MERCURE_INTERNAL_URL,
    KEY_FILE,
    CERT_FILE
} from "./config.mjs";

const app = express();
const router = express.Router();

let server;
try {
    if (
        checkFileContains(CERT_FILE, "BEGIN CERTIFICATE") &&
        checkFileContains(KEY_FILE, "BEGIN PRIVATE KEY")
    ) {
        server = https.createServer({
            key: fs.readFileSync(KEY_FILE),
            cert: fs.readFileSync(CERT_FILE)
        }, app);
    } else {
        server = http.createServer(app);
    }
} catch {
    server = http.createServer(app);
}

export const io = new Server(server, {
    path: "/ws",
    cors: { origin: "*", methods: ["GET", "POST"] }
});

await setupRedisAdapter(io);

io.use((socket, next) => {
    const token = socket.handshake.query.token;
    if (!token) return next(new Error("Auth error"));

    jwt.verify(token, WEBSOCKET_SECRET, (err, decoded) => {
        if (err) return next(new Error("Auth error"));
        socket.user = decoded;
        next();
    });
});

io.on("connection", async (socket) => {
    const jwtObj = socket.user;

    for (const room of jwtObj.rooms || []) {
        socket.join(room);
    }

    await loginUser(socket);

    socket.emit("sendOnlineUser", JSON.stringify(await getOnlineUsers()));

    socket.on("disconnect", async () => {
        await disconnectUser(socket);
        io.emit("sendOnlineUser", JSON.stringify(await getOnlineUsers()));
    });
});

app.use(bodyParser.json());
app.use(bodyParser.urlencoded({ extended: false }));

router.post(MERCURE_INTERNAL_URL, async (req, res) => {
    const token = req.headers.authorization?.split(" ")[1];
    if (!token) return res.sendStatus(403);

    jwt.verify(token, WEBSOCKET_SECRET, () => {
        io.to(req.body.topic).emit("mercure", req.body.data);
        res.end("OK");
    });
});

router.get("/healthz", (_, res) => res.sendStatus(200));

app.use("/", router);

server.listen(PORT, () => {
    console.log(`Worker ${process.pid} listening on ${PORT}`);
});

