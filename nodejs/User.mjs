import {io} from "./websocket.js";
import {getOnlineUSer, getUserStatus} from "./login.mjs";
import {AWAY_TIME} from "./config.mjs";

class User {
    userId;
    status;
    oldStatus = null;
    awayTimer = null;
    sockets = [];
    inMeeting = [];
    offline = false;
    away = false;

    constructor(userId, socket, status) {
        this.sockets.push(socket);
        this.status = status;
        this.initUserAway();
        this.userId = userId;
        this.offline = status === 'offline';
    }

    addSocket(socket) {
        if ((this.sockets.indexOf(socket) === -1)) {
            this.sockets.push(socket);
        }
    }

    removeSocket(socket) {
        const index = this.sockets.indexOf(socket);
        if (index > -1) { // only splice array when item is found
            this.sockets.splice(index, 1); // 2nd parameter means remove one item only
        }

    }

    setStatus(status) {
        if (status === 'offline') {
            this.offline = true;
        } else {
            this.offline = false;
        }
        this.status = status;
        this.initUserAway();
    }

    hasSocket(socket) {
        if (socket in this.sockets) {
            return true
        }
        return false;
    }

    getSockets() {
        return this.sockets
    }

    getStatus() {
        if (this.offline || this.sockets.length === 0) {
            return 'offline';
        }
        if (this.inMeeting.length > 0) {
            return 'inMeeting';
        }
        if (this.away){
            return 'away';
        }
        return this.status;
    }

    setAlive() {
        this.initUserAway()
    }

    sendStatus() {
        io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
        for (var s of this.sockets) {
            s.emit('sendUserStatus', this.status);
        }
    }

    getUserId() {
        return this.userId;
    }

    setuserId(value) {
        this._userId = value;
    }

    initUserAway() {
        clearTimeout(this.awayTimer);
        this.awayTimer = null;
        var that = this;
        if (this.away === true) {
            this.away = false;
            this.sendStatus();
        }
        this.oldStatus = null;
        this.awayTimer = setTimeout(function () {
            that.away = true;
            that.sendStatus();
        }, 60000 * AWAY_TIME)
    }

    enterMeeting(socket) {
        if (!this.inMeeting.includes(socket)) {
            this.inMeeting.push(socket);
        }
    }

    leaveMeeting(socket) {
        for (var i = 0; i < this.inMeeting.length; i++) {
            if (this.inMeeting[i] === socket) {
                this.inMeeting.splice(i, 1);
            }
        }
    }

    checkUserLeftTheApp() {
        return this.sockets.length === 0;

    }
}

export {User};