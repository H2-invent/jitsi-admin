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
    checkEmptySockets, 
    setAwayTime, 
    getStatusForListOfIds
} from './login.mjs'

export async function websocketState(event, socket, message) {

    switch (event) {
        case 'disconnect':
            disconnectUser(socket);
            setTimeout(async function () {
                if (checkEmptySockets(socket)) {
                    io.emit('sendOnlineUser', JSON.stringify(await getOnlineUSer()));
                    console.log('Send is Offline');
                }
                await sendStatus(socket);
            }, 7000);
            break;

        case 'login': 
            break;

        case 'setStatus':
            await setStatus(socket, message);
            await sendStatus(socket);
            break;

        case 'getStatus':
            io.emit('sendOnlineUser', JSON.stringify(await getOnlineUSer()));
            break;

        case 'getMyStatus':
            socket.emit('sendUserStatus', getUserStatus(socket));
            break;

        case 'stillOnline':
            stillOnline(socket);
            break;

        case 'enterMeeting':
            enterMeeting(socket);
            await sendStatus(socket);
            break;

        case 'leaveMeeting':
            leaveMeeting(socket);
            await sendStatus(socket);
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

async function sendStatus(socket) {
    await sendStatusToOwnUSer(socket);
    io.emit('sendOnlineUser', JSON.stringify(await getOnlineUSer()));
}

async function sendStatusToOwnUSer(socket) {
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
