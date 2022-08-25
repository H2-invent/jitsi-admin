import {sendViaWebsocket, token} from "./websocket";

let status = document.getElementById('onlineSelector').dataset.status;

export function initStatus() {
    $('.changeStatus').click(function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (href !== '#') {
            $.get(href);
        }
        var target = this.closest('.onlineSelector').querySelector('#onlineSelector');
        target.dataset.status = this.dataset.status;
        target.innerHTML = this.innerHTML;
        status = this.dataset.status;
        setStatus();
    })

    document.addEventListener('visibilitychange', function (event) {
        if (document.hidden) {
            sendViaWebsocket('inWindow');
        }
    });
    setInterval(function () {
        sendViaWebsocket('inWindow');
    },60000);

}

export function setStatus() {
        sendViaWebsocket('setStatus', status);
}

export function showOnlineUsers(data) {

    var $adressbookLine = Array.prototype.slice.call(document.querySelectorAll('.adressbookline'));
    var setMe = false;
    for (var status in data) {
        for (var i = 0; i < $adressbookLine.length; i++) {
            if (data[status].includes($adressbookLine[i].dataset.uid)) {
                $adressbookLine[i].dataset.status = status
                $adressbookLine.splice(i, 1);
            }
        }
        if (data[status].includes(document.getElementById('onlineSelector').dataset.uid)) {
            var switcher = document.getElementById('onlineSelector')
            switcher.dataset.status = status;
            var query = '.changeStatus[data-status="'+status+'"]';
            var source = document.querySelector(query)
            var innerHtml = source.innerHTML;
           switcher.innerHTML = innerHtml;
           setMe = true
        }
    }
    for (var k in $adressbookLine) {
        $adressbookLine[k].dataset.status = 'offline'
    }
    if (!setMe){
        setStatus();
    }
}

export function getStatus() {
    sendViaWebsocket('getStatus')
}