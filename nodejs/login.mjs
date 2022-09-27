import jwt from 'jsonwebtoken'
import {User} from "./User.mjs";
import {WEBSOCKET_SECRET} from "./config.mjs";

let user = {};

export function loginUser(socket) {

    if (jwt.verify(socket.handshake.query.token, WEBSOCKET_SECRET)) {
        var userId = getUserId(socket);
        console.log('create new user');
        if (userId){
            if (typeof user[userId] === 'undefined') {
                user[userId] = new User(userId, socket, getUserInitialOnlineStatus(socket));
            } else {
                console.log('add user');
                user[userId].addSocket(socket);
            }
            return user[userId]
        }
    }
    return null;
}


export function disconnectUser(socket) {
    var userId = getUserId(socket);
    leaveMeeting(socket);
    if (user[userId]) {
        user[userId].removeSocket(socket);
    }
}

export function checkEmptySockets(socket) {
    try {
        return user[getUserId(socket)].checkUserLeftTheApp()
    }catch (e) {
        return false;
    }

}

export function setStatus(socket, status) {
    var userId = getUserId(socket);
    user[userId].setStatus(status);
    return user[userId];
}

export function setAwayTime(socket, awayTime) {
    var user = getUserFromSocket(socket);
    user.setAwayTime(awayTime);
    return user;
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

export function getUserStatus(socket) {
    if (user[getUserId(socket)]) {
        return user[getUserId(socket)].getStatus();
    }
}

function getUserId(socket) {
    var jwtObj = jwt.decode(socket.handshake.query.token);
    var userId = jwtObj.sub
    return userId;
}

function getUserInitialOnlineStatus(socket) {
    var jwtObj = jwt.decode(socket.handshake.query.token);
    return jwtObj.status === 1 ? 'online' : 'offline';
}

export function getUserFromSocket(socket) {
    var userId = getUserId(socket);
    return user[userId] ? user[userId] : null;
}
export function getStatusForListOfIds(socket,list) {
    var list = JSON.parse(list);
    var res = {};
    for (var l of list){
        try {
            var tmpUser = user[l];
            if (tmpUser){
                res[l] = tmpUser.getStatus();
            }else {
                res[l] = 'offline';
            }
        }catch (e) {
            res[l] = 'offline';
        }
    }

    socket.emit('giveOnlineStatus',JSON.stringify(res));
}