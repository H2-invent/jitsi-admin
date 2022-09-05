import {sendViaWebsocket} from "./websocket";

var status ;
var login = true;
export function initStatus() {
    status = document.getElementById('onlineSelector') ? document.getElementById('onlineSelector').dataset.status : null;

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
        getMyStatus();

}

export function setStatus() {
    if (status) {
        sendViaWebsocket('setStatus', status);
    }
}

export function showOnlineUsers(data) {
    status = document.getElementById('onlineSelector') ? document.getElementById('onlineSelector').dataset.status : null;

    if (status) {
        var $adressbookLine = Array.prototype.slice.call(document.querySelectorAll('.adressbookline'));
        var setMe = false;
        for (var status in data) {
            for (var i = 0; i < $adressbookLine.length; i++) {
                if (data[status].includes($adressbookLine[i].dataset.uid)) {
                    $adressbookLine[i].dataset.status = status
                    $adressbookLine.splice(i, 1);
                }
            }
        }
        for (var k in $adressbookLine) {
            $adressbookLine[k].dataset.status = 'offline'
        }
    }
}

export function setMyStatus(status){
        var switcher = document.getElementById('onlineSelector')
        switcher.dataset.status = status;
        var query = '.changeStatus[data-status="' + status + '"]';
        var source = document.querySelector(query)
        var innerHtml = source.innerHTML;
        switcher.innerHTML = innerHtml;
}

export function getMyStatus() {
    sendViaWebsocket('getMyStatus')
}