import jwt from 'jsonwebtoken'

let user = {};

export function loginUser(socket) {
    if (jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET)) {
        var jwtObj = jwt.decode(socket.handshake.query.token);
        user[socket.id] = {userId: jwtObj.sub, status: 'online'};
    }
}

export function logoutUser(sockId) {
    delete user[sockId];
}

export function setStatus(socket, status) {
    if (user[socket.id]) {
        user[socket.id].status = status;
    }
}

export function getOnlineUSer() {
    var tmpUser = {};
    for (var prop in user) {
        var tmpStatus = user[prop].status
        if (typeof tmpUser[tmpStatus] ==='undefined'){
            tmpUser[tmpStatus] = [];
        }
        tmpUser[tmpStatus].push(user[prop].userId);
    }
    return tmpUser;
}