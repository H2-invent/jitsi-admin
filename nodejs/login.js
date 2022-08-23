import jwt from 'jsonwebtoken'

let user = {};
let sockets = {};

export function loginUser(socket) {
    if (jwt.verify(socket.handshake.query.token, process.env.WEBSOCKET_SECRET)) {
        var jwtObj = jwt.decode(socket.handshake.query.token);
        user[jwtObj.sub] = 'online';
        sockets[socket.id] = jwtObj.sub;
    }
}

export function logoutUser(sockId) {
    var userId = sockets[sockId];
    delete  sockets[sockId]
    for (var s in sockets){
        if (sockets[s] === userId){
            delete sockets[s]
        }
    }
    delete user[userId];
}
export function disconnectUser(sockId) {
    var userId = sockets[sockId];
    delete  sockets[sockId]
    for (var s in sockets){
        if (sockets[s] === userId){
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
    for (var prop in user) {
        var tmpStatus = user[prop].status
        if (typeof tmpUser[tmpStatus] === 'undefined') {
            tmpUser[tmpStatus] = [];
        }
        tmpUser[tmpStatus].push(user[prop].userId);
    }
    return tmpUser;
}