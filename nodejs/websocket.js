import {createServer} from "http";
import {Server} from "socket.io";
import jwt from 'jsonwebtoken'

import {loginUser, logoutUser, getOnlineUSer, setStatus} from './login.js'
import {websocketState} from './websocketState.js';

const httpServer = createServer();
export const io = new Server(httpServer, {
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

    socket.on('disconnect', function () {
        logoutUser(socket.id)
        setTimeout(function () {
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
        }, 5000);

    })

    socket.onAny( function (event,data) {
        websocketState(event, socket, data);
    })
})


httpServer.listen(3000);