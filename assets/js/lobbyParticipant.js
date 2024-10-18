/*
 * Welcome to your app's main JavaScript file!
 *
 */
import 'regenerator-runtime/runtime'
import {initNotofication} from './lobbyNotification';
import {initCircle} from './initCircle';
import {choosenLabelFull, initWebcam, stopWebcam, toggle} from './cameraUtils';
import {echoOff, initAUdio, micLabelFull} from './audioUtils';
import {initAjaxSend} from './confirmation';
import {setSnackbar} from './myToastr';
import {initGenerell} from './init';
import {leaveMeeting, socket} from './websocket';
import {close, inIframe, initModeratorIframe} from './moderatorIframe';
import {initStarSend} from './endModal';
import {moveTag} from './moveTag';
import Swal from 'sweetalert2'

import {JitsiUtils} from "./jitsiUtils";
import {LivekitUtils} from "./livekit/livekitUtils";

let jitsiUtils = null;
let liveKitUtils = null;
//teil ohne JItsi zusammehang

function closeForAll() {
    api = null;
    initStarSend();
}

initNotofication(closeForAll);
initAjaxSend(confirmTitle, confirmCancel, confirmOk);

function checkCloseParticipant() {//funktion um die webcam und das micro zu testen
    if (!jitsiUtils && !liveKitUtils){
        close();
    }
    echoOff(); // Echo ausschalten wenn noch an
    stopWebcam(); // Webcam ausschalten
    const res = askHangup(); // prüfen ob der Teilnehmer in einer Konferenz ist, und wenn, dann fragen ob die Konferenz beendet werden soll
    if (!res) { // wenn nicht nachgefragt werden muss (Der Teilnehmer ist noch nicht in der Konferenz, sondern erst in der Lobby)
        closeBrowser();
        leaveMeeting();
        close();
    }
}

initModeratorIframe(checkCloseParticipant);

function initMercure() { // funktion um die kontext spezifischenbefehle vom websocket zu verarbeiten.
    socket.on('mercure', function (inData) {
        const data = JSON.parse(inData);
        if (data.type === 'newJitsi') {
            userAccepted(data);
        } else if (data.type === 'redirect') {
            // Handle redirect
        }
    });
}

// window.onbeforeunload = function () {
//     if (!clickLeave) {
//         closeBrowser();
//     }
//     return null;
// };

function closeBrowser() {
    fetch(browserLeave)
        .then( () => {
            if (inIframe()) {
                close();
            } else {
                try {
                    window.close();
                    location.href = "/";
                } catch (e) {
                    window.location.href = "/";
                }
            }
        });

    for (let i = 0; i < 500000000; i++) {
        // Intentionally empty loop for delay
    }
}

initCircle();


var counter = 0;
var interval;
var intervalRenew;
var text;

document.getElementById('renewParticipant').addEventListener('click', function (e) {
    e.preventDefault();
    if (counter <= 0) {
        counter = reknockingTime;
        text = e.target.textContent;
        fetch(e.target.getAttribute('href'))
            .then(response => response.json())
            .then(data => {
                intervalRenew = setInterval(() => {
                    counter -= 1;
                    e.target.textContent = `${text} (${counter})`;
                    if (counter <= 0) {
                        e.target.textContent = text;
                        clearInterval(intervalRenew);
                    }
                }, 1000);
                setSnackbar(data.message, data.color);
            });
    }
});
document.querySelectorAll('.leave').forEach(element => {
    element.addEventListener('click', function (e) {
        e.preventDefault();
        clickLeave = true;
        browserLeave = element.getAttribute('href');
        closeBrowser();
    });
});


let api;
var conferenceOptions;
var clickLeave = false;
var displayName = null;
var avatarUrl = null;

function prepareVideoFrame() {
    stopWebcam();
    echoOff();
    window.onbeforeunload = null;

    const body = document.querySelector('body');
    body.insertAdjacentHTML('afterbegin', '<div id="frame"></div>');

    const frameDiv = document.getElementById('frame');
    const logoImage = document.getElementById('logo_image');
    logoImage.setAttribute('href', '#');
    logoImage.classList.add('stick');
    const jitsiWindow = document.getElementById('jitsiWindow');
    jitsiWindow.insertAdjacentElement('afterbegin', logoImage);
    frameDiv.insertAdjacentElement('afterbegin', jitsiWindow);
    // logoImage.insertAdjacentElement('afterbegin', document.getElementById('jitsiWindow'));
    //
    // frameDiv.insertAdjacentHTML('afterbegin', document.getElementById('jitsiWindow'));
    moveTag(frameDiv);
    document.getElementById('window').remove();
    document.getElementById('mainContent').remove();
    // document.querySelector('.imageBackground').remove();
    console.log(options);
    document.title = options.roomName;

    frameDiv.insertAdjacentHTML('beforeend', '<div id="snackbar" class="bg-success d-none"></div>');
}


function userAccepted(data) {
    conferenceOptions = options;

    if (data.options.jwt) {
        conferenceOptions.jwt = data.options.jwt;
    }
    document.getElementById('renewParticipant').remove();
    document.querySelector('.overlay').remove();
    document.querySelector('.accessAllowed').classList.remove('d-none');
    counter = 10;
    document.getElementById('lobby_participant_counter').textContent = counter;
    document.getElementById('stopEntry').classList.remove('d-none');

    interval = setInterval(() => {
        counter -= 1;
        const counterElement = document.getElementById('lobby_participant_counter');
        counterElement.style.transition = 'opacity 0s';
        counterElement.style.opacity = '0';
        setTimeout(() => {
            counterElement.style.transition = 'opacity 0.5s';
            counterElement.style.opacity = '1';
        }, 1);
        if (counter < 0) {
            clearInterval(interval);
            prepareVideoFrame();
            startConference(conferenceOptions);

        }
        counterElement.textContent = counter;
    }, 1000);

    document.getElementById('stopEntry').addEventListener('click', function () {
        if (interval) {
            clearInterval(interval);
            interval = null;
            text = this.dataset.alternativ;
            document.querySelectorAll('.textAllow').forEach((ele)=>{
                ele.remove();
            })

            this.textContent = text;
        } else {
            prepareVideoFrame();
            startConference(conferenceOptions);

        }
    });
}

document.addEventListener('DOMContentLoaded', function () {
    initGenerell();
    initAUdio();
    initWebcam();
    initMercure();

    const webcamRow = document.getElementById('webcamRow');
    const webcamArea = document.querySelector('.webcamArea');
    webcamRow.style.height = `${webcamArea.clientHeight}px`;

    const ro = new ResizeObserver(entries => {
        for (let entry of entries) {
            webcamRow.style.height = `${webcamArea.clientHeight}px`;
        }
    });

    ro.observe(webcamArea);
});

function startConference(options){
    if (typeof livekitUrl!== 'undefined'){
        liveKitUtils =  new LivekitUtils('jitsiWindow', livekitUrl+'&jwt='+options.jwt,toggle,choosenLabelFull,micLabelFull);
        //here start the livekitu confernece
    }else {
        initJitsiMeet(options);
    }
}
function initJitsiMeet(data) {


    data.parentNode = document.getElementById('jitsiWindow');

    if (data.userInfo.avatarUrl) {
        avatarUrl = data.userInfo.avatarUrl;
    }
    if (data.userInfo.displayName) {
        displayName = data.userInfo.displayName;
    }
    jitsiUtils = new JitsiUtils(data, jitsiDomain,  toggle, choosenLabelFull, micLabelFull,askHangup)


}

function askHangup() {
    if (!jitsiUtils && !liveKitUtils) {
        return false;
    }
    if (liveKitUtils){
        liveKitUtils.hangup();
        return false;
    }

    // SweetAlert2 Bestätigung
    Swal.fire({
        title: '',
        text: hangupQuestion,
        icon: 'question',
        showCancelButton: true,
        confirmButtonText: hangupText,
        cancelButtonText: cancel,
        customClass: {
            confirmButton: 'btn-danger btn',
            cancelButton:  'btn-outline-primary btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            jitsiUtils.hangup();
        }
    });

    return true;
}




//
// function switchCameraOn(videoOn) {
//     if (videoOn === true) {
//         api.isVideoMuted().then(muted => {
//             if (muted) {
//                 api.executeCommand('toggleVideo');
//             }
//         });
//     } else {
//         api.isVideoMuted().then(muted => {
//             if (!muted) {
//                 api.executeCommand('toggleVideo');
//             }
//         });
//     }
// }


