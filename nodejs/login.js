import jwt from 'jsonwebtoken'

let user = {};
export function loginUser(socket, data) {
    if (jwt.verify(data,process.env.WEBSOCKET_SECRET)){
        var jwtObj = jwt.decode(data);
        user[socket.id] = jwtObj.sub;
        for (var i = 0; i<jwtObj.rooms.length; i++){
            socket.join(jwtObj.rooms[i]);
        }
    }
}

export function logoutUser(sockId) {
    delete user[sockId];
}

export function getOnlineUSer() {
    var tmpUSer = [];
    for (var prop in user) {
    tmpUSer.push(user[prop]);
    }
    return tmpUSer;
}