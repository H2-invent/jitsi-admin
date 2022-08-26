import {loginUser, getOnlineUSer, setStatus, stillOnline, enterMeeting, leaveMeeting} from './login.js'
import {io} from './websocket.js'

export function websocketState(event, socket, message) {

    switch (event) {
        case 'login':
            loginUser(socket);
            socket.broadcast.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'setStatus':
            loginUser(socket);
            setStatus(socket,message)
            socket.broadcast.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'getStatus':
            socket.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'inWindow':
            socket.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'stillOnline':
            stillOnline(socket);
            break;
        case 'enterMeeting':
            enterMeeting(socket);
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'leaveMeeting':
            leaveMeeting(socket);
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        default:
            console.log(event);
            console.log('not known')
            break;
    }
}
