import {
    loginUser,
    getOnlineUSer,
    setStatus,
    stillOnline,
    enterMeeting,
    leaveMeeting,
    getUserStatus,
    getUserFromSocket,
    disconnectUser,
    checkEmptySockets, setAwayTime, getStatusForListOfIds
} from './login.mjs'
import {io} from './websocket.js'

export function websocketState(event, socket, message) {

    switch (event) {
        case 'disconnect':
            disconnectUser(socket);
            setTimeout(function () {
                if (checkEmptySockets(socket)) {
                    io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
                    console.log('Send is Offline');
                }
                sendStatus(socket);
            }, 7000);
            break;
        case 'login'://fügt den SOcket zu dem USer hinzu. Schickt keine Benachrichtigungen an die anderen Clients
            break;
        case 'setStatus'://setzt den Status und informiert alle Clients, das sich der Status geändert hat
            setStatus(socket, message);
            sendStatus(socket);
            break;
        case 'getStatus':
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'getMyStatus':
            socket.emit('sendUserStatus', getUserStatus(socket));
            break;
        case 'stillOnline':
            stillOnline(socket);
            break;
        case 'enterMeeting':
            enterMeeting(socket);
            sendStatus(socket);
            break;
        case 'leaveMeeting':
            leaveMeeting(socket);
            sendStatus(socket);
            break;
        case 'openNewIframe':
            sendNewIframe(socket, message)
            break;
        case 'giveOnlineStatus':
            getStatusForListOfIds(socket, message);
            break;
        case 'setAwayTime':
            setAwayTime(socket, message);
            break;
        default:
            console.log(event);
            console.log('not known')
            break;
    }
}

function sendStatus(socket) {
    sendStatusToOwnUSer(socket);
    io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
}

function sendStatusToOwnUSer(socket) {
    var user = getUserFromSocket(socket)
    if (user) {
        user.sendToAllSockets('sendUserStatus', user.getStatus());
        user.sendToAllSockets('sendAwayTime', user.awayTime);
    }
}

function sendNewIframe(socket, data) {
    var message = JSON.parse(data);
    socket.to(message.room).emit('openNewIframe', JSON.stringify({
                url: message.url,
                title: message.title
            }
        )
    )
}
