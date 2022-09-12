import jwt from 'jsonwebtoken'
import {User} from "./User.mjs";

let user = {};

export function loginUser(socket) {
    console.log('loginUser');
    if (jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET)) {
        var userId = getUserId(socket);

        if (typeof user[userId] === 'undefined') {
            console.log('add new User');
            user[userId] = new User(userId, socket, 'online');
        } else {
            user[userId].addSocket(socket);
        }
      socket.emit
    }
}


export function disconnectUser(socket) {
    console.log('remove User');
    var userId = getUserId(socket);
    leaveMeeting(socket);
    if(user[userId]){
        user[userId].removeSocket(socket);
    }
}

export function checkEmptySockets() {
    var deleted = false;
    for (var prop in user) {
        if (user[prop].getSockets().length === 0) {
            delete user[prop];
            deleted = true;
        }
    }
    return deleted;
}


export function setStatus(socket, status) {
    var userId = getUserId(socket);
    user[userId].setStatus(status);
    return user[userId];
}

export function stillOnline(socket) {
    if (user[getUserId(socket)]) {
        user[getUserId(socket)].initUserAway();
    }
    return 0;
}

export function enterMeeting(socket) {
    if (user[getUserId(socket)]) {
        user[getUserId(socket)].enterMeeting(socket);
    }
    return 0;
}

export function leaveMeeting(socket) {
    if (user[getUserId(socket)]) {
        user[getUserId(socket)].leaveMeeting(socket);
    }
    return 0;
}

export function getOnlineUSer() {
    var tmpUser = {};

    for (var prop in user) {
        var u = user[prop];

        var tmpStatus = u.getStatus();
        if (typeof tmpUser[tmpStatus] === 'undefined') {
            tmpUser[tmpStatus] = [];
        }
        tmpUser[tmpStatus].push(u.getUserId());
    }
    return tmpUser;
}
export function getUserStatus(socket){
    if (user[getUserId(socket)]) {
        return user[getUserId(socket)].getStatus();
    }
}

function getUserId(socket) {
    var jwtObj = jwt.decode(socket.handshake.query.token);
    var userId = jwtObj.sub
    return userId;
}
export function getUserFromSocket(socket){
    var userId = getUserId(socket);
    return user[userId]?user[userId]:null;
}