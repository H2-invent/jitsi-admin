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
import stc from 'string-to-color/index';
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId} from './cameraUtils'
import {initAUdio, micId, audioId,echoOff} from './audioUtils'
import {initJitsi} from './jitsiUtils'
var jitsiApi;
navigator.mediaDevices.getUserMedia({audio: true, video: true})
initNotofication();
initAUdio();
initWebcam();


const es = new EventSource(topic);
es.onmessage = e => {
    var data = JSON.parse(e.data)
    masterNotify(data)
}

$('.directSend').click(function (e) {
    e.preventDefault();
    $.get($(this).attr('href'), function (data) {
        $('#snackbar').text(data.message).removeClass('d-none').addClass('show bg-' + data.color).click(function (e) {
            $('#snackbar').removeClass('show');
        })
    })
})

const broadcast = new EventSource(topicBroadcast);
broadcast.onmessage = e => {
    var data = JSON.parse(e.data);
    masterNotify(data);
}

$('.startIframe').click(function (e) {
    e.preventDefault();
    echoOff();
    document.title = conferenzeName;
    $(this).remove();
    $('#colWebcam').remove();
    $('#col-waitinglist').removeClass('col-lg-9 col-md-6').addClass('col-12');
    moveWrapper();
    options.devices={
        audioInput: choosenId,
        audioOutput: audioId,
        videoInput: micId
    }
    initJitsi(options,domain);

    $('#jitsiWrapper').find('iframe').css('height', '100vh');
})

function moveWrapper() {
    $('#jitsiWrapper').prependTo('body').css('height', '100vh').find('#jitsiWindow').css('height', 'inherit');
    $('#snackbar').appendTo('body');
    $('#jitsiWindow').css('height', '100vh');
    $('#content').remove();
    $('.imageBackground').remove();
    $('.lobbyWindow').wrap('<div class="container-fluid waitinglist" id="sliderTop">').append('<div class="dragger">Lobby</div>');
    $('#col-waitinglist').addClass('large');
    $('#sliderTop').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
}

initCircle();


