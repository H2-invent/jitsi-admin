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

    var hidden, visibilityChange;
    if (typeof document.hidden !== "undefined") { // Opera 12.10 and Firefox 18 and later support
        hidden = "hidden";
        visibilityChange = "visibilitychange";
    } else if (typeof document.msHidden !== "undefined") {
        hidden = "msHidden";
        visibilityChange = "msvisibilitychange";
    } else if (typeof document.webkitHidden !== "undefined") {
        hidden = "webkitHidden";
        visibilityChange = "webkitvisibilitychange";
    }

    function handleVisibilityChange() {
        if (document[hidden]) {
        } else {
            sendViaWebsocket('stillOnline');
        }
    }

    if (typeof document.addEventListener === "undefined" || typeof document[hidden] === "undefined") {
        console.log(" requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
    } else {
        document.addEventListener(visibilityChange, handleVisibilityChange, false);
    }

    setInterval(function () {
        handleVisibilityChange();
    }, 60000)
}

export function sendViaWebsocket(event, message) {
    socket.emit(event, message);
}

