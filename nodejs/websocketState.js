import {
    loginUser,
    getOnlineUSer,
    setStatus,
    stillOnline,
    enterMeeting,
    leaveMeeting,
    getUserStatus,
    getUserFromSocket, disconnectUser, checkEmptySockets
} from './login.js'
import {io} from './websocket.js'

export function websocketState(event, socket, message) {

    switch (event) {
        case 'disconnect':
            disconnectUser(socket);
            setTimeout(function () {
                if (checkEmptySockets()) {
                    io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
                }
            }, 7000);
            sendStatus(socket);
            break;
        case 'login'://fügt den SOcket zu dem USer hinzu. Schickt keine Benachrichtigungen an die anderen Clients
            loginUser(socket);
            break;
        case 'setStatus'://setzt den Status und informiert alle Clients, das sich der Status geändert hat
            loginUser(socket);
            var tmp = setStatus(socket, message);
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
        default:
            console.log(event);
            console.log('not known')
            break;
    }
}

function sendStatus(socket) {
    var user = getUserFromSocket(socket)
    if (user) {
        for (var prop in user.getSockets()) {
            var tmpSocket = user.getSockets()[prop];
            tmpSocket.emit('sendUserStatus', getUserStatus(tmpSocket));
        }
    }
    io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
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
