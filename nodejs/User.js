// import {io} from "./websocket.js";
// import {getOnlineUSer} from "./login.js";
//
// class User {
//     userId;
//     status;
//     oldStatus = null;
//     awayTimer = null;
//     sockets = [];
//
//     constructor(userId, socket, status) {
//         this.sockets.push(socket);
//         this.status = status;
//         var that = this;
//         this.oldStatus = null;
//         this.awayTimer = setTimeout(function () {
//             that.oldStatus = that.status;
//             that.status = 'away';
//             that.sendStatus();
//         }, 120000)
//         this._userId = userId;
//     }
//
//     addSocket(socket) {
//         this.sockets.push(socket);
//     }
//
//     removeSocket(socket) {
//         for (var i = 0; i < this.sockets.length; i++) {
//
//             if (this.sockets[i] === socket) {
//                 this.sockets.splice(i, 1);
//             }
//         }
//     }
//
//     setStatus(status, socket) {
//         this.status = status;
//         socket.broadcast.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
//     }
//
//     getStatus() {
//         return this.status
//     }
//
//     hasSocket(socket) {
//         if (socket in this.sockets) {
//             return true
//         }
//         return false;
//     }
//
//     setAlive() {
//         clearTimeout(this.awayTimer);
//         this.awayTimer = null;
//         if (this.oldStatus !== null) {
//             this.status = this.oldStatus;
//             this.oldStatus = null;
//             this.sendStatus();
//         }
//         var that = this;
//         this.awayTimer = setTimeout(function () {
//             that.oldStatus = that.status;
//             that.status = 'away';
//         }, 120000)
//     }
//
//     sendStatus() {
//         io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
//     }
//
//     getuserId() {
//         return this._userId;
//     }
//
//     setuserId(value) {
//         this._userId = value;
//     }
// }
//
// export {User};