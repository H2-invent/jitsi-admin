/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import $ from 'jquery';
import {initDragDragger} from './lobby_dragger'
import {initDragParticipants} from './lobby_moderator_acceptDragger'
global.$ = global.jQuery = $;
import * as mdb from 'mdb-ui-kit'; // lib
import ('jquery-confirm');
import stc from 'string-to-color/index';
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff} from './audioUtils'
import {initJitsi, hangup} from './jitsiUtils'
import {initAjaxSend} from './confirmation'
import {initGenerell} from './init';
import {disableBodyScroll}  from 'body-scroll-lock'

var jitsiApi;
try {
    navigator.mediaDevices.getUserMedia({audio: true, video: true})
} catch ($e) {
    console.log($e)
}

initNotofication();
initAUdio();
initWebcam();
initAjaxSend(confirmTitle, confirmCancel, confirmOk);
let es;

function initMercure() {
    connectES();
    setInterval(function () {

        if (es.readyState === 2) {
            connectES();
        }
    }, 5000);
}

function connectES() {
    es = new EventSource([topic]);
    es.onmessage = e => {
        var data = JSON.parse(e.data)
        masterNotify(data);
        if (data.type === 'endMeeting') {
            hangup();
        }
    }
}


$('.startIframe').click(function (e) {
    e.preventDefault();
    echoOff();
    document.title = conferenzeName;
    $(this).remove();
    $('#colWebcam').remove();
    $('#col-waitinglist').removeClass('col-lg-9 col-md-6').addClass('col-12');

    moveWrapper();
    options.devices = {
        audioInput: choosenId,
        audioOutput: audioId,
        videoInput: micId
    }
    window.onbeforeunload = function () {
        return '';
    }


    initJitsi(options, domain, confirmTitle, confirmOk, confirmCancel);

    $('#jitsiWindow').find('iframe').css('height', '100%');
    window.scrollTo(0, 1)
    initDragDragger();
    document.querySelector('body').classList.add('touchactionNone');
    // document.getElementsByTagName('body').style.width='100%';

    window.addEventListener("scroll", (e) => {
        e.preventDefault();
        window.scrollTo(0, 0);
    });
})

function moveWrapper() {
    stopWebcam();

    $('body').prepend('<div id="frame"></div>');

    var frameDIv = $('#frame');
    frameDIv.prepend($('#jitsiWindow').addClass('inMeeting'));
    $('#logo_image').prop('href', '#').addClass('stick').prependTo('#jitsiWindow');
    frameDIv.prepend($('#jitsiWrapper'));
    frameDIv.prepend($('#tagContent').removeClass().addClass('floating-tag'));
    frameDIv.append($('#snackbar'))
    $('#mainContent').remove();
    $('.imageBackground').remove();
    $('#lobbyWindow').wrap('<div class="container-fluid waitinglist" id="sliderTop">').append('<div class="dragger" id="dragger">Lobby ( <span id="lobbyCounter">' + $('.waitingUserCard').length + '</span> )</div>');
    $('#col-waitinglist').addClass('large');

    $('#sliderTop').css('top', '0px');
    $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
    window.addEventListener('resize', function () {
        $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
    });
    let childElement = document.querySelectorAll('.waitingUserCard ');
    childElement.forEach(function (e) {
        var iconHolder = e.querySelector('.icon-holder');
        var height = $(e.querySelector('.card')).innerHeight();
        let width = $(e.querySelector('.card')).innerWidth();
        $(iconHolder).height(height + 'px');
        $(iconHolder).width(width + 'px');
    });
    $('.start-btn').remove();
    $('.btn-block').removeClass('btn-block');
}

initCircle();

$(document).ready(function () {
    initGenerell();
    initMercure();
    initDragParticipants();
})


