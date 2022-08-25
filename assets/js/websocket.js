import {io} from "socket.io/client-dist/socket.io";
import {getStatus, setStatus, showOnlineUsers} from "./onlineStatus";
import {masterNotify} from "./lobbyNotification";

export var socket = null;
export var token = null;


export function initWebsocket(jwt) {
    token = jwt;
    socket = io(websocketUrl, {
        query: {token}
    });

    socket.on('connect', function (data) {
        getStatus();
    });
    socket.on('sendOnlineUser', function (data) {
        showOnlineUsers(JSON.parse(data))
    })
    socket.on('mercure', function (data) {
        masterNotify(JSON.parse(data));
    })
    function handleVisibilityChange() {
        if (!document.hidden) {
            sendViaWebsocket('stillOnline');
        }
    }
    setInterval(function () {
        handleVisibilityChange();
    },60000)
    document.addEventListener("visibilitychange", handleVisibilityChange, false);
}

export function sendViaWebsocket(event, message) {
    socket.emit(event, message);
}

