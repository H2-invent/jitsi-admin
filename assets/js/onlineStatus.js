import {sendViaWebsocket, token} from "./websocket";

let status = document.getElementById('onlineSelector').classList[0];

export function initStatus() {
    $('.changeStatus').click(function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (href !== '#') {
            $.get(href);
        }
        var target = this.closest('.onlineSelector').querySelector('#onlineSelector');
        target.className = '';
        target.classList.add(this.dataset.value);
        target.classList.add('show');
        target.innerHTML = this.innerHTML;
        status = this.dataset.value;
        setStatus();
    })
}

export function setStatus() {
    if (status === 'offline') {
        sendViaWebsocket('logout', []);
    } else if (status === 'online') {
        sendViaWebsocket('login', token);
    } else {
        sendViaWebsocket('setStatus', status);
    }
}

export function showOnlineUsers(data) {

    var $adressbookLine = Array.prototype.slice.call( document.querySelectorAll('.adressbookline'));

    for (var status in data) {
        for (var i = 0; i < $adressbookLine.length; i++) {
            if (typeof $adressbookLine[i] !== 'undefined' && data[status].includes($adressbookLine[i].dataset.uid)) {
                $adressbookLine[i].dataset.status = status
                $adressbookLine.splice(i, 1);
            }
        }

    }
    for (var k in $adressbookLine){

            $adressbookLine[k].dataset.status = 'offline'

    }
}