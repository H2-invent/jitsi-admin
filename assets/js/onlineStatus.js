import {sendViaWebsocket} from "./websocket";
import {categorySort} from "./addressGroup"

var status;
var login = true;
var profillLine = null;

export function initStatus() {
    if (!document.getElementById('onlineSelector')) {
        return;
    }
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
        document.getElementById('onlineSelector').innerHTML = this.innerText;
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
                    try {
                        var $filter = JSON.parse($adressbookLine[i].dataset.filterafter);
                        $filter = cleanFilterArr($filter)
                        $filter.push(status);
                        $adressbookLine[i].dataset.filterafter = JSON.stringify($filter);
                    } catch (e) {

                    }

                    $adressbookLine[i] = undefined;
                }
            }
        }
        for (var k in $adressbookLine) {
            if ($adressbookLine[k]) {
                $adressbookLine[k].dataset.status = 'offline'
            }

        }
        categorySort();
    }
}

function cleanFilterArr($input) {
    $input = $input.filter(e => e != 'online');
    $input = $input.filter(e => e != 'offline');
    $input = $input.filter(e => e != 'away');
    $input = $input.filter(e => e != 'inMeeting');
    return $input;
}

export function setMyStatus(status) {
    var switcher = document.getElementById('onlineSelector')
    if (switcher) {
        profillLine.dataset.status = status;
        var query = '.changeStatus[data-status="' + status + '"]';
        var source = document.querySelector(query)
        var text = source.innerText;

        switcher.innerHTML = text;
    }

}

export function getMyStatus() {
    sendViaWebsocket('getMyStatus')
}