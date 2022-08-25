import jwt from 'jsonwebtoken'
import {User} from "./User.js";

let user = {};

export function loginUser(socket) {
    if (jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET)) {
        var userId = getUserId(socket);

        if (typeof user[userId] === 'undefined') {
            console.log('add User');
            user[userId] = new User(userId, socket, 'online');
        } else {
            user[userId].addSocket(socket);
        }
    }
}


export function disconnectUser(socket) {
    var userId = getUserId(socket);
    user[userId].removeSocket(socket);
    if (user[userId].getSockets().length === 0) {
        delete user.userId
    }
}


export function setStatus(socket, status) {
    var userId = getUserId(socket);
    user[userId].setStatus(status);
}

export function stillOnline(socket){
    user[getUserId(socket)].initUserAway();
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
    console.log(tmpUser);
    return tmpUser;
}

function getUserId(socket) {
    var jwtObj = jwt.decode(socket.handshake.query.token);
    var userId = jwtObj.sub
    return userId;
}