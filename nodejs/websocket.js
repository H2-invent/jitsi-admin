import express from 'express';
import bodyParser from "body-parser";

const router = express.Router();
const app = express();
import http from 'http'

const server = http.createServer(app);
import {Server} from "socket.io";
import jwt from 'jsonwebtoken'

import {getOnlineUSer, disconnectUser, checkEmptySockets} from './login.js'
import {websocketState} from './websocketState.js';

export const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    }
});

io.use(function (socket, next) {
        if (socket.handshake.query && socket.handshake.query.token) {
            jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET, function (err, decoded) {
                if (err) return next(new Error('Authentication error'));
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

    socket.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
    socket.on('disconnect', function () {
        websocketState('disconnect', socket, null);
    })

    socket.onAny(function (event, data) {
        websocketState(event, socket, data);
    })
})
app.use(bodyParser.urlencoded({extended: false}));
app.use(bodyParser.json());
router.post('/.well-known/mercure', (request, response) => {
//code to perform particular action.
//To access POST variable use req.body()methods.

    const authHeader = request.headers.authorization;
    if (authHeader) {
        const token = authHeader.split(' ')[1];

        jwt.verify(token, process.env.WEBSOCKET_SECRET, (err, user) => {
            if (err) {

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

app.use("/", router);

server.listen(3000, () => {
    console.log('listening on *:3000');
});