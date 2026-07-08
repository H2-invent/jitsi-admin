import express from 'express';
import bodyParser from "body-parser";

const router = express.Router();
const app = express();
import http from 'http'
import https from "https";
import fs from "fs";

import {checkFileContains} from './checkCertAndKey.js';
import {Server} from "socket.io";
import jwt from 'jsonwebtoken'

import {getOnlineUSer, loginUser} from './login.mjs'
import {websocketState} from './websocketState.mjs';
import {MERCURE_INTERNAL_URL, PORT, WEBSOCKET_SECRET, KEY_FILE, CERT_FILE} from "./config.mjs";


const keyPath = KEY_FILE;
const certPath = CERT_FILE;
let server;
try {
    if (checkFileContains(certPath, 'BEGIN CERTIFICATE') && checkFileContains(keyPath, 'BEGIN PRIVATE KEY')) {
        console.log('We found the cert and the key file');
        console.log('The cert and key are valid.')
        console.log('We start an HTTPS Server.');
        server = https.createServer({
                key: fs.readFileSync(keyPath),
                cert: fs.readFileSync(certPath)
            },
            app);
    } else {
        server = http.createServer(app);
    }
} catch (err) {
    console.error(err)
    server = http.createServer(app);
}


export const io = new Server(server, {
    path: '/ws',
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    }
});

io.use(function (socket, next) {
        if (socket.handshake.query && socket.handshake.query.token) {
            jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET, function (err, decoded) {
                if (err) {
                    console.log('wrong secret. Check your secrets');
                    return next(new Error('Authentication error'));
                }
                socket.decoded = decoded;
                next();
            });
        } else {

            next(new Error('Authentication error'));
        }
    }
)

io.on("connection", async (socket) => {
    var jwtObj = jwt.decode(socket.handshake.query.token);
    for (var i = 0; i < jwtObj.rooms.length; i++) {
        socket.join(jwtObj.rooms[i]);
    }
    var user = loginUser(socket);

    if (user) {
        user.initUserAway();
        socket.emit('sendUserStatus', user.getStatus());
        socket.emit('sendUserTimeAway', user.awayTime);
        io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
    }

    socket.on('disconnect', function () {
        websocketState('disconnect', socket, null);
    })

    socket.onAny(function (event, data) {
        websocketState(event, socket, data);
    })
})
app.use(bodyParser.urlencoded({extended: false}));
app.use(bodyParser.json());
router.post(MERCURE_INTERNAL_URL, (request, response) => {
//code to perform particular action.
//To access POST variable use req.body()methods.
    console.log('Receive new Backend Request');
    const authHeader = request.headers.authorization;
    if (authHeader) {
        const token = authHeader.split(' ')[1];

        jwt.verify(token, WEBSOCKET_SECRET, (err, user) => {
            if (err) {
                console.log('Wrong JWT signature');
                return response.sendStatus(403);
            } else {

                var data = request.body.data;
                var room = request.body.topic;
                io.to(room).emit('mercure', data);

                response.end('OK');
            }
        });
    } else {

        response.sendStatus(403);
        response.end('OK');
    }
});
router.get(MERCURE_INTERNAL_URL, (request, response) => {
//code to perform particular action.
//To access POST variable use req.body()methods.
    return response.sendStatus(200);
});
router.get('/healthz', (request, response) => {
//code to perform particular action.
//To access POST variable use req.body()methods.
    return response.sendStatus(200);
});

app.use("/", router);
server.listen(PORT, () => {
    console.log('listening on *:' + PORT);
});