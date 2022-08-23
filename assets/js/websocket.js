import {io} from "socket.io/client-dist/socket.io";
import {getCookie} from "./cookie";

export function initWebsocket(jwt) {
    var token = jwt;
    var socket = io('ws://localhost:3000', {
        query: {token}
    });

    socket.on('connect', function (data) {
        socket.emit('login', token);
    });


    socket.on('sendOnlineUSer', function (data) {
        data = JSON.parse(data);
        var $adressbookLine = document.querySelectorAll('.adressbookline');
        for (var i = 0; i<$adressbookLine.length; i++) {
            if (data.includes($adressbookLine[i].dataset.uid)) {
                $adressbookLine[i].classList.add('isOnline');
            } else {
                $adressbookLine[i].classList.remove('isOnline');
            }
        }
    })
    // setInterval(function () {
    //     socket.emit('getOnlineUSer');
    // }, 10000);
}