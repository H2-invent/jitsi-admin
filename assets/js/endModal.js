import $ from "jquery";
import {close, inIframe} from "./moderatorIframe";

export var starShowed = false;
export var initilized = false;

export function initStarSend() {
    if (starShowed) {
        closeWindow();
    }
    $('#endMeetingModal').removeClass('d-none');
    $('#mainContent').remove();
    $('#frame').remove();
    if ($('.starSend').length > 0 && initilized == false) {
        $('.starSend').click(
            function (e) {
                e.preventDefault();
                $('.starSend').remove();
                var _navigator = {};
                for (var i in window.navigator) _navigator[i] = navigator[i];
                const params = {
                    server: $(this).data('serverid'),
                    comment: document.querySelector('#comment').value,
                    star: $(this).data('star'),
                    browser: JSON.stringify(_navigator)
                }

                fetch($(this).data('server') + '?' + new URLSearchParams(params), {
                    method: 'get',
                    mode: 'no-cors',
                    headers: {'Content-Type': 'application/json'},
                }).then(res => {
                    closeWindow();
                });
            }
        )
        initilized = true;
    } else {
        setTimeout(function () {
            closeWindow();
        }, popupWatingTime);
    }

    $('#closeWindow').click(function () {
        closeWindow();
    })
    setTimeout(function () {
        starShowed = true;

    }, 2000);
}

function closeWindow() {
    if (inIframe()) {
        close();
    } else {
        window.onbeforeunload = null;//setze die Anchfrage ob das Ffenster geshclossen werden soll
        close();//schließe das Fenster wenn es ein Iframe ist
        window.location.href = '/';// leite weiter an die Url in dem Comand
    }
}