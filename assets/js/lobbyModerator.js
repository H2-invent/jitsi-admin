/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import $ from 'jquery';
import {initDragDragger} from './lobby_dragger'

global.$ = global.jQuery = $;

import ('jquery-confirm');
import { initNotofication} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId, stopWebcam, toggle, choosenLabelFull} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff, micLabelFull} from './audioUtils'
import {JitsiUtils} from './jitsiUtils'
import {initAjaxSend} from './confirmation'
import {initGenerell} from './init';
import { leaveMeeting, socket} from "./websocket";
import {initModeratorIframe, close} from './moderatorIframe'
import {initSearchCallOut} from "./inviteCalloutUser";
import {initSendMessage} from "./sendMessageToWaitingUser";
import {LivekitUtils} from "./livekit/livekitUtils";
import Swal from "sweetalert2";


let jitsiUtils = null;
let livekitUtil = null;
var cancel = "Abbrechen";


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
    $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
    window.scrollTo(0, 1)
    initDragDragger();
    document.querySelector('body').classList.add('touchactionNone');
    window.onbeforeunload = function () {
        return '';
    }
    if (typeof livekitUrl !== 'undefined') {
        livekitUtil = new LivekitUtils('jitsiWindow', livekitUrl,toggle,choosenLabelFull,micLabelFull);
    } else {
        options.devices = {
            audioInput: micId,
            audioOutput: audioId,
            videoInput: choosenId
        }
        jitsiUtils = new JitsiUtils(options, domain, toggle, choosenLabelFull, micLabelFull, askHangup);
        $('#jitsiWindow').find('iframe').css('height', '100%');
    }


    // document.getElementsByTagName('body').style.width='100%';

    window.addEventListener("scroll", (e) => {
        e.preventDefault();
        window.scrollTo(0, 0);
    });
});

function askHangup() {
    if (!jitsiUtils && !livekitUtil) {
        return false;
    }
    if (livekitUtil) {
        livekitUtil.hangup();
        return true;

    }
    // SweetAlert2 Bestätigung
    Swal.fire({
        title: '',
        text: hangupQuestion,
        icon: 'question',
        showDenyButton: true,
        denyButtonText: endMeetingText,
        showCancelButton: true,
        confirmButtonText: hangupText,
        cancelButtonText: cancel,
        customClass: {
            confirmButton: 'btn-danger btn',
            denyButton: 'btn-danger btn',
            cancelButton: 'btn-outline-primary btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            jitsiUtils.hangup();
        } else if (result.isDenied) {
            jitsiUtils.endMeeting();
        }
    });

    return true;
}

function moveWrapper() {
    stopWebcam();

    // Neues Div für den Rahmen erstellen und am Anfang des body hinzufügen
    const frameDiv = document.createElement('div');
    frameDiv.id = 'frame';
    document.body.prepend(frameDiv);

    // JitsiWindow verschieben und bearbeiten
    const jitsiWindow = document.getElementById('jitsiWindow');
    jitsiWindow.classList.add('inMeeting');
    frameDiv.prepend(jitsiWindow);

    // Logo-Bild bearbeiten und in jitsiWindow verschieben
    const logoImage = document.getElementById('logo_image');
    logoImage.href = '#';
    logoImage.classList.add('stick');
    logoImage.classList.remove('d-none');
    jitsiWindow.prepend(logoImage);

    // jitsiWrapper verschieben
    const jitsiWrapper = document.getElementById('jitsiWrapper');
    frameDiv.prepend(jitsiWrapper);

    // Snackbar verschieben
    const snackbar = document.getElementById('snackbar');
    frameDiv.append(snackbar);

    // mainContent und imageBackground entfernen
    const mainContent = document.getElementById('mainContent');
    if (mainContent) {
        mainContent.remove();
    }

    const imageBackgrounds = document.querySelectorAll('.imageBackground');
    imageBackgrounds.forEach(el => el.remove());

    // LobbyWindow umhüllen und Lobby-Dragger hinzufügen
    const lobbyWindow = document.getElementById('lobbyWindow');
    const containerDiv = document.createElement('div');
    containerDiv.classList.add('container-fluid', 'waitinglist');
    containerDiv.id = 'sliderTop';
    lobbyWindow.parentNode.insertBefore(containerDiv, lobbyWindow);
    containerDiv.appendChild(lobbyWindow);

    const draggerDiv = document.createElement('div');
    draggerDiv.classList.add('dragger');
    draggerDiv.id = 'dragger';
    draggerDiv.innerHTML = 'Lobby ( <span id="lobbyCounter">' + document.querySelectorAll('.waitingUserCard').length + '</span> )';
    lobbyWindow.appendChild(draggerDiv);

    // col-waitinglist vergrößern
    const colWaitinglist = document.getElementById('col-waitinglist');
    if (colWaitinglist) {
        colWaitinglist.classList.add('large');
    }

    // Slider positionieren
    const updateSliderPosition = () => {
        const colHeight = colWaitinglist ? colWaitinglist.offsetHeight : 0;
        containerDiv.style.top = '0px';
        containerDiv.style.transform = 'translateY(-' + colHeight + 'px)';
    };
    updateSliderPosition();

    window.addEventListener('resize', updateSliderPosition);

    // Größe der Icon-Holder anpassen
    const waitingUserCards = document.querySelectorAll('.waitingUserCard');
    waitingUserCards.forEach(e => {
        const iconHolder = e.querySelector('.icon-holder');
        const card = e.querySelector('.card');
        const height = card ? card.offsetHeight : 0;
        const width = card ? card.offsetWidth : 0;
        if (iconHolder) {
            iconHolder.style.height = height + 'px';
            iconHolder.style.width = width + 'px';
        }
    });

    // start-btn und btn-block bearbeiten
    const startBtns = document.querySelectorAll('.start-btn');
    startBtns.forEach(el => el.remove());

    const btnBlocks = document.querySelectorAll('.btn-block');
    btnBlocks.forEach(el => el.classList.remove('btn-block'));
}

initCircle();

$(document).ready(function () {
    initGenerell();
    initMercure();

    const customBoundary = document.querySelector('body');

})
