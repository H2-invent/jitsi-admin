import { io } from "./websocket.js";
import { getOnlineUSer } from "./login.mjs";
import { AWAY_TIME, DEFAULT_STATE } from "./config.mjs";

class User {
    userId;
    status;
    oldStatus = null;
    awayTimer = null;
    sockets = [];
    inMeeting = [];
    offline = false;
    away = false;
    awayTime = 0;

    constructor(userId, socket, status) {
        this.userId = userId;
        this.status = status;
        this.sockets.push(socket);
        this.offline = status === DEFAULT_STATE;
        this.awayTime = AWAY_TIME;
        this.initUserAway(); // async, kein await im Konstruktor möglich
    }

    addSocket(socket) {
        if (!this.sockets.includes(socket)) this.sockets.push(socket);
    }

    removeSocket(socket) {
        const index = this.sockets.indexOf(socket);
        if (index > -1) this.sockets.splice(index, 1);
    }

    getStatus() {
        if (this.offline || this.sockets.length === 0) return 'offline';
        if (this.inMeeting.length > 0) return 'inMeeting';
        if (this.away) return 'away';
        return this.status;
    }

    async sendStatus() {
        const onlineUsers = await getOnlineUSer();
        io.emit('sendOnlineUser', JSON.stringify(onlineUsers));
        this.sendToAllSockets('sendUserStatus', this.getStatus());
    }

    async initUserAway() {
        clearTimeout(this.awayTimer);
        this.awayTimer = null;

        if (this.away === true) {
            this.away = false;
            await this.sendStatus();
        }

        this.oldStatus = null;
        this.awayTimer = setTimeout(async () => {
            this.away = true;
            await this.sendStatus();
        }, 60000 * this.awayTime);
    }

    async setStatus(status) {
        this.status = status;
        this.offline = status === 'offline';
        await this.initUserAway();
    }

    sendToAllSockets(ev, message) {
        for (const socket of this.sockets) socket.emit(ev, message);
    }

    enterMeeting(socket) {
        if (!this.inMeeting.includes(socket)) this.inMeeting.push(socket);
    }

    leaveMeeting(socket) {
        const idx = this.inMeeting.indexOf(socket);
        if (idx > -1) this.inMeeting.splice(idx, 1);
    }

    checkUserLeftTheApp() {
        return this.sockets.length === 0;
    }

    async setAwayTime(awayTime) {
        try {
            awayTime = parseInt(awayTime);
            if (Number.isInteger(awayTime)) {
                this.awayTime = awayTime;
                this.sendToAllSockets('sendUserTimeAway', this.awayTime);
            }
        } catch (e) {
            console.error(e);
        }
    }
}

export { User };
