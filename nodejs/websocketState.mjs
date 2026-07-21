import { getIO } from "./ioRegistry.mjs";
import {
    getOnlineUser,
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
            await disconnectUser(socket);
            setTimeout(async function () {
                if (await checkEmptySockets(socket)) {
                    getIO().emit('sendOnlineUser', JSON.stringify(await getOnlineUser()));
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
            getIO().emit('sendOnlineUser', JSON.stringify(await getOnlineUser()));
            break;

        case 'getMyStatus':
            socket.emit('sendUserStatus', await getUserStatus(socket));
            break;

        case 'stillOnline':
            await stillOnline(socket);
            break;

        case 'enterMeeting':
            await enterMeeting(socket);
            await sendStatus(socket);
            break;

        case 'leaveMeeting':
            await leaveMeeting(socket);
            await sendStatus(socket);
            break;

        case 'openNewIframe':
            console.log(message);
            sendNewIframe(socket, message)
            break;

        case 'giveOnlineStatus':
            getStatusForListOfIds(socket, message);
            break;

        case 'setAwayTime':
            await setAwayTime(socket, message);
            break;

        default:
            console.log(event);
            console.log('not known')
            break;
    }
}

async function sendStatus(socket) {
    await sendStatusToOwnUSer(socket);
    getIO().emit('sendOnlineUser', JSON.stringify(await getOnlineUser()));
}

async function sendStatusToOwnUSer(socket) {
    var user = getUserFromSocket(socket)
    if (user) {
        user.sendToAllSockets('sendUserStatus', user.getStatus());
        user.sendToAllSockets('sendAwayTime', user.awayTime);
    }
}

function sendNewIframe(socket, data) {
    try {
        var message = JSON.parse(data);
        socket.to(message.room).emit('openNewIframe', JSON.stringify({
                    url: message.url,
                    title: message.title
                }
            )
        )
    }catch (e) {
        console.log(e)
    }

}
