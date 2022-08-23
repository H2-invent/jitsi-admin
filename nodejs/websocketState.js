import {loginUser, logoutUser, getOnlineUSer, setStatus} from './login.js'
import {io} from './websocket.js'

export function websocketState(event, socket, message) {

    switch (event) {
        case 'login':
            loginUser(socket);
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'logout':
            logoutUser(socket.id)
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        case 'setStatus':
            loginUser(socket);
            setStatus(socket,message)
            io.emit('sendOnlineUser', JSON.stringify(getOnlineUSer()));
            break;
        default:
            console.log('not known')
            break;
    }
}
