import {sendViaWebsocket} from "./websocket";

var status;
var login = true;
var profillLine = null;
export function initStatus() {
    profillLine = document.getElementById('onlineSelector').closest('.profile').querySelector('.profileLine');
    status = profillLine ? profillLine.dataset.status : null;

    $('.changeStatus').click(function (e) {
        e.preventDefault();
        var href = this.getAttribute('href');
        if (href !== '#') {
            $.get(href);
        }
        var target = this.closest('.profile').querySelector('.profileLine');
        profillLine.dataset.status = this.dataset.status;
        document.getElementById('onlineSelector').innerHTML = this.innerHTML;
        status = this.dataset.status;
        setStatus();
    })
}

export function setStatus() {
    if (status) {
        sendViaWebsocket('setStatus', status);
    }
}

export function showOnlineUsers(data) {
    status = document.querySelectorAll('.adressbookline') ? document.querySelectorAll('.adressbookline') : null;

    if (status) {
        var $adressbookLine = Array.prototype.slice.call(document.querySelectorAll('.adressbookline'));
        var setMe = false;
        for (var status in data) {
            for (var i = 0; i < $adressbookLine.length; i++) {
                if (typeof $adressbookLine[i] !== 'undefined' && data[status].includes($adressbookLine[i].dataset.uid)) {
                    $adressbookLine[i].dataset.status = status
                    $adressbookLine[i] = undefined;
                }
            }
        }
        for (var k in $adressbookLine) {
            if ($adressbookLine[k]) {
                $adressbookLine[k].dataset.status = 'offline'
            }

        }
    }
}

export function setMyStatus(status) {
    var switcher = document.getElementById('onlineSelector')
    if (switcher) {
        profillLine.dataset.status = status;
        var query = '.changeStatus[data-status="' + status + '"]';
        var source = document.querySelector(query)
        var innerHtml = source.innerHTML;
        switcher.innerHTML = innerHtml;
    }

}

export function getMyStatus() {
    sendViaWebsocket('getMyStatus')
}