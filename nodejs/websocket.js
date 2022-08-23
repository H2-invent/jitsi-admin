import {createServer} from "http";
import {Server} from "socket.io";
import jwt from 'jsonwebtoken'

import {loginUser, logoutUser, getOnlineUSer} from './login.js'


const httpServer = createServer();
const io = new Server(httpServer, {
    cors: {
        origin: "*",
        methods: ["GET", "POST"],
    }
});

io.use(function (socket,next) {
        if (socket.handshake.query && socket.handshake.query.token){
            jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET, function(err, decoded) {
                if (err) return next(new Error('Authentication error'));
                socket.decoded = decoded;
                next();
            });
        }
        else {
            next(new Error('Authentication error'));
        }
    }
)

io.on("connection", async (socket) => {
    socket.on('disconnect', function () {
        logoutUser(socket.id)
        setTimeout(function (){
            io.emit('sendOnlineUSer', JSON.stringify(getOnlineUSer()));
        },5000);

    })
    socket.on('login', function (data) {
        loginUser(socket, data);
        io.emit('sendOnlineUSer', JSON.stringify(getOnlineUSer()));
    })
    socket.on('getOnlineUSer', function (data) {
        socket.emit('sendOnlineUSer', JSON.stringify(getOnlineUSer()));
    });
})


httpServer.listen(3000);