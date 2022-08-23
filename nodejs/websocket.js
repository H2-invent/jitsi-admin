import { createServer } from "http";
import { Server } from "socket.io";
import * as socketioJwt from 'socketio-jwt';

import {loginUser, logoutUser, getOnlineUSer} from './login.js'
const httpServer = createServer();
const io = new Server(httpServer, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    }
});

io.on("connection", (socket) => {
    console.log('new USer connected')
    socket.on('disconnect', function () {
        logoutUser(socket.id)
    })
    socket.on('login',function (data) {
        loginUser(socket.id, data);
    })
    socket.on('getOnlineUSer',function (data) {
        socket.emit('sendOnlineUSer',JSON.stringify(getOnlineUSer()));
    })
});

httpServer.listen(3000);