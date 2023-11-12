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
import {initWebcam, choosenId, stopWebcam, toggle, webcamArr} from './cameraUtils'
import {initAUdio, micId, echoOff, micArr} from './audioUtils'
import {initAjaxSend} from './confirmation'
import {setSnackbar} from './myToastr';
import {initGenerell} from './init';
import {enterMeeting, leaveMeeting, socket} from './websocket';
import {initModeratorIframe, close, inIframe, showPlayPause} from './moderatorIframe'
import {initStarSend} from "./endModal";
import {initStartWhiteboard} from "./startWhiteboard";
import {checkDeviceinList} from './jitsiUtils'
import {jitsiController} from "./pauseJitsi";
import {initSocialIcons} from "./createSocialButtons";
import {moveTag} from "./moveTag";
import {ConferenceUtils} from "./ConferenceUtils";

import ('jquery-confirm');


function closeForAll() {
    api = null;
    initStarSend();
}
initNotofication(closeForAll);
initAjaxSend(confirmTitle, confirmCancel, confirmOk);

function checkCloseParticipant() {
    echoOff();//echo ausschlaten wenn ncoh an
    stopWebcam();//Webcam auschalten
    var res = askHangup();//prüfen ob der Teilenhmer in einer Konferenz ist, und wenn, dann fragen ob die Konferenz beendet werden soll
    if (!res) {//wenn nciht neachgefragt werden muss (Der Teilnehmer ist noch nicht in der Konferenz, sondern erst in der lobby)
        // sende ein LEavmeeting an den Websocket und sende ein CloaseMe an das Parent
        closeBrowser();
        leaveMeeting();
        close();
    }
}
initModeratorIframe(checkCloseParticipant);
let api;
var dataSucess;
var successTimer;
var clickLeave = false;
var microphoneLabel = null;
var cameraLable = null;
var displayName = null;
var avatarUrl = null;
let conferenceUtils= null;
function initMercure() {

    socket.on('mercure', function (inData) {
        var data = JSON.parse(inData)
        if (data.type === 'newJitsi') {
            userAccepted(data);
        } else if (data.type === 'redirect') {

        }
    })
}

window.onbeforeunload = function (e) {
    if (!clickLeave) {
        closeBrowser();
    }
    return null;
};

function closeBrowser() {
    fetch(browserLeave)
        .then(response => {
            if (inIframe()) {
                close()
            } else {
                try {
                    window.close();
                    location.href = "/";
                }catch (e) {
                    window.location.href = "/";
                }
            }

        });

    for (var i = 0; i < 500000000; i++) {
    }

}

initCircle();
var counter = 0;
var interval;
var intervalRenew;
var text;

$('.renew').click(function (e) {
    e.preventDefault();
    if (counter <= 0) {
        counter = reknockingTime;
        text = $(this).text();
        $.get($(this).attr('href'), function (data) {
            intervalRenew = setInterval(function () {
                counter = counter - 1;
                $('.renew').text(text + ' (' + counter + ')');
                if (counter <= 0) {
                    $('.renew').text(text);
                    clearInterval(intervalRenew);
                }
            }, 1000);
            setSnackbar(data.message, data.color);
        })
    }
})
$('.leave').click(function (e) {
    e.preventDefault();
    clickLeave = true;
    var url = this.getAttribute('href');
    browserLeave = url;
    closeBrowser();
})

function initJitsiMeet(data) {
    cameraLable = webcamArr[choosenId];
    microphoneLabel = micArr[micId];
    stopWebcam();
    echoOff();
    window.onbeforeunload = null;

    $('body').prepend('<div id="frame"></div>');

    var frameDIv = $('#frame');
    $('#logo_image').prop('href', '#').addClass('stick').prependTo('#jitsiWindow');
    frameDIv.prepend($(data.options.parentNode));
    moveTag(frameDIv)
    $('#window').remove();
    $('#mainContent').remove();
    $('.imageBackground').remove();
    document.title = data.options.roomName
    frameDIv.append('<div id="snackbar" class="bg-success d-none"></div>')

    var options = data.options.options;
    options.device = choosenId;


    options.parentNode = document.querySelector(data.options.parentNode);
    if (typeof options.userInfo.avatarUrl !== 'undefined'){
        avatarUrl = options.userInfo.avatarUrl;
    }
    if (typeof options.userInfo.displayName !== 'undefined'){
        displayName = options.userInfo.displayName;
    }
    api = new JitsiMeetExternalAPI(data.options.domain, options);
    conferenceUtils = new ConferenceUtils(api);
    conferenceUtils.initConferencePreJoin();
    api.addListener('chatUpdated', function (e) {
        if (e.isOpen == true) {
            document.querySelector('#logo_image').classList.add('transparent');
        } else {
            document.querySelector('#logo_image').classList.remove('transparent');
        }

    });

    api.addListener('videoConferenceJoined', function (e) {
        enterMeeting();
        initStartWhiteboard();
        showPlayPause();
        conferenceUtils.initConferencePostJoin();
        var pauseController = new jitsiController(api,displayName,avatarUrl);
        window.onbeforeunload = function (e) {
            return 'Do you really want to leave this conference';
        }
        if (typeof enforceE2Eencryption !== 'undefined'){
            if (enforceE2Eencryption){
                api.executeCommand('toggleE2EE', true);
            }
        }
        api.addListener('videoConferenceLeft', function (e) {
            leaveMeeting();
            initStarSend();
            api = null;
        });

        if (setTileview === 1) {
            api.executeCommand('setTileView', {enabled: true});
        }
        if (setParticipantsPane === 1) {
            api.executeCommand('toggleParticipantsPane', {enabled: true});
        }
        if (avatarUrl !== '') {
            api.executeCommand('avatarUrl', avatarUrl);
        }
        api.getAvailableDevices().then(devices => {
            if (checkDeviceinList(devices, cameraLable)) {
                api.setVideoInputDevice(checkDeviceinList(devices, cameraLable));
            }
            if (checkDeviceinList(devices, microphoneLabel)) {
                api.setAudioInputDevice(checkDeviceinList(devices, microphoneLabel));
            }
            swithCameraOn(toggle);
        });
        swithCameraOn(toggle);


        api.addListener('participantKickedOut', function (e) {
            var id = api.getParticipantsInfo();

        });

    });


    $(data.options.parentNode).find('iframe').css('height', '100%');
    window.scrollTo(0, 1)

}


function askHangup() {
    if (!api) {
        return false;
    }
    $.confirm({
        title: null,
        content: hangupQuestion,
        theme: 'material',
        columnClass: 'col-md-8 col-12 col-lg-6',
        buttons: {
            confirm: {
                text: hangupText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    hangup();
                },
            },
            cancel: {
                text: cancel, // text for button
                btnClass: 'btn-outline-primary btn', // class for the button
            },
        }
    });
    return true;
}

function hangup() {
    api.executeCommand('hangup')
}

function userAccepted(data) {
    dataSucess = data;
    $('#renewParticipant').remove();
    $('.overlay').remove();
    $('.accessAllowed').removeClass('d-none');
    counter = 10;
    $('#lobby_participant_counter').text(counter);
    $('#stopEntry').removeClass('d-none');

    interval = setInterval(function () {
        counter = counter - 1;
        $('#lobby_participant_counter').css('transition', ' opacity 0s');
        $('#lobby_participant_counter').css('opacity', '0');
        setTimeout(function () {
            $('#lobby_participant_counter').css('transition', ' opacity 0.5s');
            $('#lobby_participant_counter').css('opacity', '1');
        }, 1)
        if (counter < 0) {
            clearInterval(interval);
            initJitsiMeet(dataSucess);
        }
        $('#lobby_participant_counter').text(counter);
    }, 1000);


    $('#stopEntry').click(function (e) {
        if (interval) {
            clearInterval(interval);
            interval = null;
            text = $(this).data('alternativ')
            $('.textAllow').remove();
            $(this).text(text);
        } else {
            initJitsiMeet(dataSucess);
        }
    })
}


function swithCameraOn(videoOn) {
    if (videoOn === 1) {
        var muted =
            api.isVideoMuted().then(muted => {

                if (muted) {
                    api.executeCommand('toggleVideo');
                }
            });
    } else {
        api.isVideoMuted().then(muted => {
            if (!muted) {
                api.executeCommand('toggleVideo');
            }

        });
    }
}

$(document).ready(function () {
    initGenerell()
    initAUdio();
    initWebcam();
    initMercure();
    $('#webcamRow').css('height', $('.webcamArea').height());
    var ro = new ResizeObserver(entries => {
        for (let entry of entries) {
            $('#webcamRow').css('height', $('.webcamArea').height());
        }
    });

// Observe one or multiple elements
    ro.observe(document.querySelector('.webcamArea'));

})



