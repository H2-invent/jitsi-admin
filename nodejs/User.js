import {io} from "./websocket.js";
import {getOnlineUSer} from "./login.js";

class User {
    userId;
    status;
    oldStatus = null;
    awayTimer = null;
    sockets = [];
    inMeeting = [];
    offline = false;
    constructor(userId, socket, status) {
        this.sockets.push(socket);
        this.status = status;
        this.initUserAway();
        this.userId = userId;
    }

    addSocket(socket) {
        if (!socket in this.sockets) {
            this.sockets.push(socket);
        }

    }

    removeSocket(socket) {
        for (var i = 0; i < this.sockets.length; i++) {
            if (this.sockets[i] === socket) {
                this.sockets.splice(i, 1);
            }
        }
    }

    setStatus(status) {
        if (status === 'offline'){
            this.offline = true;
        }else {
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
        if (this.offline){
            return 'offline';
        }
        if (this.inMeeting.length > 0) {
            return 'inMeeting';
        }
        return this.status;
    }

    setAlive() {
        this.initUserAway()
    }

    sendStatus() {
        io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
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
        if (this.oldStatus !== null) {
            this.status = this.oldStatus;
            this.sendStatus();
        }
        this.oldStatus = null;
        this.awayTimer = setTimeout(function () {
            if (that.status !== 'offline') {
                that.oldStatus = that.status;
                that.status = 'away';
            }
            that.sendStatus();
        }, 60000 * process.env.AWAY_TIME)
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
}

export {User};