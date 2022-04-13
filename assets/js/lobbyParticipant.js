/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff} from './audioUtils'
import {initAjaxSend} from './confirmation'
import {setSnackbar} from './myToastr';
import {initGenerell} from './init';


initNotofication();

initAjaxSend(confirmTitle, confirmCancel, confirmOk);

const es = new EventSource(topic);
var api;
var dataSucess;
var successTimer;
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

    text = $(this).text();
    $.get($(this).attr('href'), function (data) {
        window.location.href = "/";
    })

})

function initJitsiMeet(data) {
    stopWebcam();
    $(data.options.parentNode).prependTo('body');
    $('#window').remove();
    $('.imageBackground').remove();
    document.title = data.options.roomName
    $('body').append('<div id="snackbar" class="bg-success d-none"></div>')

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

    $(data.options.parentNode).css('height', '100vh').find('iframe').css('height', '100vh');


}

function hangup() {
    api.command('hangup')
}

function userAccepted(data) {
    dataSucess = data;
    console.log('1.234')
    $('#renewParticipant').remove();
    $('#stopEntry').removeClass('d-none');
    text =  $('#stopEntry').text();
    counter = 10;
    interval = setInterval(function () {
        counter = counter - 1;
        $('#stopEntry').text(text + ' (' + counter + ')');
        if (counter <= 0) {
            $('#stopEntry').text(text);
            clearInterval(interval);
        }
    }, 1000);
    successTimer = setTimeout(function () {
        initJitsiMeet(dataSucess);


    }, 10000)

    $('#stopEntry').click(function (e) {
        if (successTimer) {
            clearTimeout(successTimer);
            clearInterval(interval);

            successTimer = null;
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
})



