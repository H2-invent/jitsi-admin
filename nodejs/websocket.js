import express from 'express';
import bodyParser from "body-parser";
import http from 'http';
import https from "https";
import fs from "fs";
import jwt from 'jsonwebtoken';
import { Server } from "socket.io";
import { createClient } from "ioredis";

import { checkFileContains } from './checkCertAndKey.js';
import { getOnlineUSer, loginUser } from './login.mjs';
import { websocketState } from './websocketState.mjs';
import { 
    MERCURE_INTERNAL_URL, 
    PORT, 
    WEBSOCKET_SECRET, 
    KEY_FILE, 
    CERT_FILE,
    REDIS_HOST = 'redis',
    REDIS_PORT = 6379
} from "./config.mjs";

const app = express();
const router = express.Router();

// --- HTTPS oder HTTP ---
let server;
try {
    if (checkFileContains(CERT_FILE, 'BEGIN CERTIFICATE') && checkFileContains(KEY_FILE, 'BEGIN PRIVATE KEY')) {
        console.log('Zertifikat & Key gefunden, starte HTTPS-Server.');
        server = https.createServer({
            key: fs.readFileSync(KEY_FILE),
            cert: fs.readFileSync(CERT_FILE)
        }, app);
    } else {
        console.log('Kein gültiges Zertifikat gefunden – starte HTTP-Server.');
        server = http.createServer(app);
    }
} catch (err) {
    console.error('Fehler beim Laden der Zertifikate:', err);
    server = http.createServer(app);
}

// --- Socket.IO Setup ---
const io = new Server(server, {
    path: '/ws',
    cors: { origin: "*", methods: ["GET", "POST"] },
});

// --- Redis optional verbinden ---
let redisEnabled = false;

try {
    const pubClient = createClient({ host: REDIS_HOST, port: REDIS_PORT });
    const subClient = pubClient.duplicate();

    await Promise.allSettled([pubClient.connect(), subClient.connect()]);

    // prüfen, ob Redis verbunden ist
    if (pubClient.status === "ready" && subClient.status === "ready") {
        const { createAdapter } = await import("@socket.io/redis-adapter");
        io.adapter(createAdapter(pubClient, subClient));
        redisEnabled = true;
        console.log(`Redis-Adapter aktiviert @ ${REDIS_HOST}:${REDIS_PORT}`);
    } else {
        console.log("Redis nicht verfügbar – Socket.IO läuft im Standalone-Modus.");
    }

    // Fehlerbehandlung
    pubClient.on("error", () => {
        console.log("Redis-Verbindung verloren – bleibe im Standalone-Modus.");
    });
} catch (err) {
    console.log("Konnte keine Redis-Verbindung herstellen – Socket.IO läuft Standalone.");
}

// --- Auth Middleware ---
io.use((socket, next) => {
    if (socket.handshake.query && socket.handshake.query.token) {
        jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET, (err, decoded) => {
            if (err) {
                console.log('Falscher JWT-Secret.');
                return next(new Error('Authentication error'));
            }
            socket.decoded = decoded;
            next();
        });
    } else {
        next(new Error('Authentication error'));
    }
});

// --- WebSocket Event Handling ---
io.on("connection", async (socket) => {
    console.log(`🔌 Neue Verbindung: ${socket.id} (${redisEnabled ? 'Cluster' : 'Standalone'})`);

    const jwtObj = jwt.decode(socket.handshake.query.token);
    if (jwtObj?.rooms) {
        jwtObj.rooms.forEach(room => socket.join(room));
    }

    const user = loginUser(socket);
    if (user) {
        user.initUserAway();
        socket.emit('sendUserStatus', user.getStatus());
        socket.emit('sendUserTimeAway', user.awayTime);
        io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
    }

    socket.on('disconnect', () => websocketState('disconnect', socket, null));
    socket.onAny((event, data) => websocketState(event, socket, data));
});

// --- Express API ---
app.use(bodyParser.urlencoded({ extended: false }));
app.use(bodyParser.json());

router.post(MERCURE_INTERNAL_URL, (req, res) => {
    console.log('Backend Request empfangen');
    const authHeader = req.headers.authorization;
    if (authHeader) {
        const token = authHeader.split(' ')[1];
        jwt.verify(token, WEBSOCKET_SECRET, (err) => {
            if (err) {
                console.log('Ungültige JWT-Signatur');
                return res.sendStatus(403);
            }
            const { topic: room, data } = req.body;
            io.to(room).emit('mercure', data);
            res.end('OK');
        });
    } else {
        res.sendStatus(403);
    }
});

router.get(MERCURE_INTERNAL_URL, (_, res) => res.sendStatus(200));
router.get('/healthz', (_, res) => res.sendStatus(200));

app.use("/", router);

// --- Server starten ---
server.listen(PORT, () => {
    console.log(`WebSocket-Server läuft auf Port ${PORT} (${redisEnabled ? 'mit Redis-Cluster' : 'Standalone'})`);
});
