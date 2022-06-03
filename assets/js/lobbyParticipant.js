/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import $ from 'jquery';

global.$ = global.jQuery = $;
import * as mdb from 'mdb-ui-kit'; // lib
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff} from './audioUtils'
import {initAjaxSend} from './confirmation'
import {setSnackbar} from './myToastr';
import {initGenerell} from './init';


initNotofication();

initAjaxSend(confirmTitle, confirmCancel, confirmOk);

var api;
var dataSucess;
var successTimer;
var clickLeave = false;
let es;

function initMercure(){
    connectES();
    setInterval(function () {
        if (es.readyState === 2) {
            connectES();
        }
    },5000);
}

function connectES() {
    es = new EventSource([topic]);
    es.onmessage = e => {
        var data = JSON.parse(e.data)
        masterNotify(data);
        if (data.type === 'newJitsi') {
            userAccepted(data);
        } else if (data.type === 'endMeeting') {
            hangup()
            $('#jitsiWindow').remove();
        }
    }
}




window.addEventListener('beforeunload', function (e) {
    if (!clickLeave){
        closeBrowser();
    }
    return;
});

function closeBrowser() {
    $.ajax({
        url: browserLeave,
        context: document.body
    })
    for (var i = 0; i < 500000000; i++) {
    }
}

initCircle();
var counter = 0;
var interval;
var text;

$('.renew').click(function (e) {
    e.preventDefault();
    if (counter <= 0) {
        counter = reknockingTime;
        text = $(this).text();
        $.get($(this).attr('href'), function (data) {
            interval = setInterval(function () {
                counter = counter - 1;
                $('.renew').text(text + ' (' + counter + ')');
                if (counter <= 0) {
                    $('.renew').text(text);
                    clearInterval(interval);
                }
            }, 1000);
            setSnackbar(data.message, data.color);
        })
    }
})
$('.leave').click(function (e) {
    e.preventDefault();
    clickLeave = true;
    $.get($(this).attr('href'), function (data) {
        window.location.href = "/";
    })
})

function initJitsiMeet(data) {
    stopWebcam();
    $('body').prepend('<div id="frame"></div>');

    var frameDIv = $('#frame');
    $('#logo_image').prop('href','#').addClass('stick').prependTo('#jitsiWindow');
    frameDIv.prepend($(data.options.parentNode));
    frameDIv.prepend($('#tagContent').removeClass().addClass('floating-tag'))
    $('#window').remove();
    $('.imageBackground').remove();
    document.title = data.options.roomName
    frameDIv.append('<div id="snackbar" class="bg-success d-none"></div>')

    var options = data.options.options;
    options.device = choosenId;
    options.parentNode = document.querySelector(data.options.parentNode);
    api = new JitsiMeetExternalAPI(data.options.domain, options);

    api.addListener('videoConferenceJoined', function (e) {
        if (setTileview === 1) {
            api.executeCommand('setTileView', {enabled: true});
        }
        if (setParticipantsPane === 1) {
            api.executeCommand('toggleParticipantsPane', {enabled: true});
        }
        if (avatarUrl !== '') {
            api.executeCommand('avatarUrl', avatarUrl);
        }
    });


    api.addListener('participantKickedOut', function (e) {

        $('#jitsiWindow').remove();
        masterNotify({'type': 'modal', 'content': endModal});
        setTimeout(function () {
            masterNotify({'type': 'endMeeting', 'url': '/'});
        }, popUpDuration)

    });

    $(data.options.parentNode).find('iframe').css('height', '100%');


}

function hangup() {
    api.command('hangup')
}

function userAccepted(data) {
    dataSucess = data;
    $('#renewParticipant').remove();
    $('#stopEntry').removeClass('d-none');
    text = $('#stopEntry').text();
    counter = 10;
    interval = setInterval(function () {
        counter = counter - 1;
        $('#stopEntry').text(text + ' (' + counter + ')');
        if (counter <= 0) {
            $('#stopEntry').text(text);
            clearInterval(interval);
            initJitsiMeet(dataSucess);
        }
    }, 1000);


    $('#stopEntry').click(function (e) {
        if (interval) {
            clearInterval(interval);
            interval = null;
            text = $(this).data('alternativ')
            $(this).text(text);
        } else {
            initJitsiMeet(dataSucess);
        }
    })
}

$(document).ready(function () {
    initGenerell()
    initAUdio();
    initWebcam();
    initMercure();
})



