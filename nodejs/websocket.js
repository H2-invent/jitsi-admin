const express = require('express');
const app = express();
const http = require('http');
const server = http.createServer(app);
const {Server} = require("socket.io");
const io = new Server(server, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    }
});

let users = {};
io.on('connection', (socket) => {
    console.log('a user connected');
    socket.on('disconnect', () => {
        console.log('user disconnected');
    });
    socket.on("login", (data) => {
        console.log(data);
        users[socket.id] = data;
    });
});
server.listen(3000, () => {
    console.log('listening on *:3000');
});
console.log('listen on ')

