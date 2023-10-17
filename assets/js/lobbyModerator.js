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
import {masterNotify, initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam, toggle, webcamArr} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff, micArr} from './audioUtils'
import {initJitsi, hangup, askHangup} from './jitsiUtils'
import {initAjaxSend} from './confirmation'
import {initGenerell} from './init';
import {leaveMeeting, socket} from "./websocket";
import {initModeratorIframe, close} from './moderatorIframe'
import {initSearchCallOut} from "./inviteCalloutUser";
import {initSendMessage} from "./sendMessageToWaitingUser";
import {moveTag} from "./moveTag";


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
initSearchCallOut();
initSendMessage();

function checkCloseModerator() {
    echoOff();//echo ausschalten wenn ncoh an
    stopWebcam();//Webcam auschalten

    var res = askHangup();//prüfen ob der Teilenhmer in einer Konferenz ist, und wenn, dann fragen ob die Konferenz beendet werden soll
    if (!res) {//wenn nciht neachgefragt werden muss (Der Teilnehmer ist noch nicht in der Konferenz, sondern erst in der lobby)
        closeIframe(); // sende ein LEavmeeting an den Websocket und sende ein CloaseMe an das Parent
    }
}

initModeratorIframe(checkCloseModerator);

export function closeIframe() {
    leaveMeeting();
    close()
}

function initMercure() {
    socket.on('mercure', function (inData) {
        var data = JSON.parse(inData)
        if (data.type === 'endMeeting') {
            hangup();
        }
    })

}


$('.startJitsiIframe').click(function (e) {
    e.preventDefault();
    echoOff();
    document.title = conferenzeName;
    $(this).remove();
    $('#colWebcam').remove();
    $('#col-waitinglist').removeClass('col-lg-9 col-md-6').addClass('col-12');

    moveWrapper();
    options.devices = {
        audioInput: micId,
        audioOutput: audioId,
        videoInput: choosenId
    }
    window.onbeforeunload = function () {
        return '';
    }
    initJitsi(options, domain, confirmTitle, confirmOk, confirmCancel, toggle, webcamArr[choosenId], micArr[micId]);
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
    $('#logo_image').prop('href', '#').addClass('stick').prependTo('#jitsiWindow').removeClass('d-none');
    frameDIv.prepend($('#jitsiWrapper'));
    moveTag(frameDIv);
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
    const customBoundary = document.querySelector('body');

})
