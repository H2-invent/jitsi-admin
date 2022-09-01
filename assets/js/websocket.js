import {io} from "socket.io/client-dist/socket.io";
import {initStatus, setMyStatus, setStatus, showOnlineUsers} from "./onlineStatus";
import {masterNotify} from "./lobbyNotification";
import {inIframe} from "./moderatorIframe";

export var socket = null;
export var token = null;
var hidden, visibilityChange;

export function initWebsocket(jwt) {
    token = jwt;
    socket = io(websocketUrl, {
        query: {token}
    });
    socket.on('mercure', function (data) {
        masterNotify(JSON.parse(data));
    })



    if (!inIframe()) {
        socket.on('connect', function (data) {
                initStatus();
        });
        socket.on('sendOnlineUser', function (data) {
                showOnlineUsers(JSON.parse(data))
        })
        socket.on('sendUserStatus', function (data) {
            if (!data){
                setStatus();
            }else {
                setMyStatus(data);
                sendViaWebsocket('login');
            }
        })
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



        if (typeof document.addEventListener === "undefined" || typeof document[hidden] === "undefined") {
            console.log(" requires a browser, such as Google Chrome or Firefox, that supports the Page Visibility API.");
        } else {
            document.addEventListener(visibilityChange, handleVisibilityChange, false);
        }

        setInterval(function () {
            handleVisibilityChange();
        }, 60000)
    }
}

function handleVisibilityChange() {
    if (document[hidden]) {
    } else {
            sendViaWebsocket('stillOnline');
    }
}
export function enterMeeting() {
    sendViaWebsocket('enterMeeting');
}

export function leaveMeeting() {
    sendViaWebsocket('leaveMeeting',);
}
export function sendViaWebsocket(event, message) {
    socket.emit(event, message);
}