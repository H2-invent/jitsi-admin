import jwt from 'jsonwebtoken'
import {User} from "./User.js";

let user = {};
let sockets = {};

export function loginUser(socket) {
    if (jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET)) {
        var jwtObj = jwt.decode(socket.handshake.query.token);
        var userId = jwtObj.sub

        if (typeof user[userId] === 'undefined' || user[userId] === null){
            console.log('add User');
            user[jwtObj.sub] = new User(jwtObj.sub,socket,'online');
        }else {
            user[jwtObj.sub].addSocket(socket);
        }
        console.log(user);
    }
}


export function logoutUser(sockId) {
    var userId = sockets[sockId];
    delete sockets[sockId]
    for (var s in sockets) {
        if (sockets[s] === userId) {
            delete sockets[s]
        }
    }
    delete user[userId];
}

export function disconnectUser(sockId) {
    var userId = sockets[sockId];
    delete sockets[sockId]
    for (var s in sockets) {
        if (sockets[s] === userId) {
            return
        }
    }
    delete user[userId];
}


export function setStatus(socket, status) {
    if (sockets[socket.id]) {
        user[sockets[socket.id]] = status;
    }
}

export function getOnlineUSer() {
    var tmpUser = {};
    for (var i = 0; i< user.length; i++) {

        var tmpStatus = user[i].getStatus();

        if (typeof tmpUser.getStatus() === 'undefined') {
            tmpUser[tmpStatus] = [];
        }
        tmpUser[tmpStatus].push(user[i].getUserId());
    }
    return tmpUser;
}