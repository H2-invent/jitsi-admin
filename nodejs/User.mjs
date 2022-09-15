import {io} from "./websocket.js";
import {getOnlineUSer, getUserStatus} from "./login.mjs";

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
        this.offline = status === 'offline';
    }

    addSocket(socket) {
        console.log('check if in  socket')
        if ((this.sockets.indexOf(socket) === -1)) {
            console.log('adduser');
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
        console.log(this.sockets.length);
        if (this.offline || this.sockets.length === 0) {
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

    checkUserLeftTheApp() {
        console.log('check User')
        console.log(this.sockets.length);
        if (this.sockets.length === 0) {
            console.log('user left the app');
            return true;
        }
        return false;
    }
}

export {User};