import $ from "jquery";
import {close, inIframe} from "./moderatorIframe";

export function initStarSend() {
    $('#endMeetingModal').modal('show');
    $('#frame').remove();
    if ($('.starSend').length > 0) {
        $('.starSend').click(
            function (e) {
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
    } else {
        setTimeout(function () {
            closeWindow();
        }, popupWatingTime);
    }

    $('#closeWindow').click(function () {
        closeWindow();
    })
}

function closeWindow() {
    if (inIframe()) {
        close();
    } else {
        window.onbeforeunload = null;//setze die Anchfrage ob das Ffenster geshclossen werden soll
        if (window.opener == null) {// wenn der aufrufende Tab nicht mehr geöffnet ist  dann
            close();//schließe das Fenster wenn es ein Iframe ist
            window.location.href = '/';// leite weiter an die Url in dem Comand
        } else {
            close();
            window.close();
        }
    }
}