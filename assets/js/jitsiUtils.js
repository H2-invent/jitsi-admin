/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';
import {enterMeeting, leaveMeeting} from "./websocket";
import {initStarSend} from "./endModal";
import {initStartWhiteboard} from "./startWhiteboard";
import {showPlayPause} from "./moderatorIframe";
import {jitsiController} from "./pauseJitsi";
import {jitsiErrorHandling} from "./jitsiErrorHandling";
import {ConferenceUtils} from "./ConferenceUtils";

global.$ = global.jQuery = $;

import ('jquery-confirm');
export let api;
var participants;

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";
var microphoneLabel = null;
var cameraLable = null;
var displayName = null;
var isMuted = null;
var isVideoMuted = null;
var avatarUrl = null;
var pauseController;
var jitsiErrorController;
var myId = null;
var roomName = null;
var isBreakout = null;
var conferenceUtils;
function initJitsi(options, domain, titelL, okL, cancelL, videoOn, videoId, micId) {
    title = titelL;
    cancel = cancelL;
    ok = okL;
    microphoneLabel = micId;
    cameraLable = videoId;
    api = new JitsiMeetExternalAPI(domain, options);
    conferenceUtils = new ConferenceUtils(api);
    conferenceUtils.initConferencePreJoin();

    if (typeof options.userInfo.avatarUrl !== 'undefined'){
        avatarUrl = options.userInfo.avatarUrl;
    }
    if (typeof options.userInfo.displayName !== 'undefined'){
        displayName = options.userInfo.displayName;
    }
    renewPartList()

    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    api.addListener('chatUpdated', function (e) {
        if (e.isOpen == true) {
            document.querySelector('#logo_image').classList.add('transparent');
        } else {
            document.querySelector('#logo_image').classList.remove('transparent');
        }

    });
    api.addListener('readyToClose', function (e) {
        leaveMeeting();
        initStarSend();
        api = null;
        endMeeting();
        var timeout = data.timeout?data.timeout:150000;
        if (window.opener == null) {
            setTimeout(function () {
                window.location.href = data.url;
            }, timeout)
        } else {
            setTimeout(function () {
                window.close();
            }, timeout)
        }
    });

    api.addListener('toolbarButtonClicked', function (e) {
        if (e.key === 'hangup') {
            askHangup()
        }
    });

    api.addListener('videoConferenceJoined', function (e) {
        enterMeeting();
        initStartWhiteboard();
        conferenceUtils.initConferencePostJoin();
        api.executeCommand('avatarUrl', avatarUrl);
        myId = e.id;
        roomName = e.roomName;
        isBreakout = e.breakoutRoom;

        pauseController = new jitsiController(api,displayName,avatarUrl,myId, roomName,isBreakout);
        jitsiErrorController= new jitsiErrorHandling(api);
        if (typeof enforceE2Eencryption !== 'undefined'){
            if (enforceE2Eencryption){
                api.executeCommand('toggleE2EE', true);
            }
        }
        $('#closeSecure').removeClass('d-none').click(function (e) {
            e.preventDefault();
            var url = $(this).prop('href');
            var text = $(this).data('text');
            $.confirm({
                title: title,
                content: text,
                theme: 'material',
                columnClass: 'col-md-8 col-12 col-lg-6',
                buttons: {
                    confirm: {
                        text: ok, // text for button
                        btnClass: 'btn-outline-danger btn', // class for the button
                        action: function () {
                            endMeeting();
                            $.getJSON(url);
                        },
                    },
                    cancel: {
                        text: cancel, // text for button
                        btnClass: 'btn-outline-primary btn', // class for the button
                    },
                }
            });

        })
        if (setTileview === 1) {
            api.executeCommand('setTileView', {enabled: true});
        }
        if (avatarUrl !== '') {
            api.executeCommand('avatarUrl', avatarUrl);
        }
        if (setParticipantsPane === 1) {
            api.executeCommand('toggleParticipantsPane', {enabled: true});
        }

        api.getAvailableDevices().then(devices => {
            if (checkDeviceinList(devices, cameraLable)) {
                api.setVideoInputDevice(checkDeviceinList(devices, cameraLable));
            }
            if (checkDeviceinList(devices, microphoneLabel)) {
                api.setAudioInputDevice(checkDeviceinList(devices, microphoneLabel));
            }
            swithCameraOn(videoOn);
        });
        swithCameraOn(videoOn);


        $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');


    });

}

function endMeeting() {
    if (!api){
        return false;
    }
    participants = api.getParticipantsInfo();
    for (var i = 0; i < participants.length; i++) {
        if (api) {
            api.executeCommand('kickParticipant', participants[i].participantId);
        }

    }
    return 0;
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
            }, killAll: {
                text: endMeetingText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    endMeeting();
                    $.getJSON(endMeetingUrl);
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
    if (api) {
        api.executeCommand('hangup');
    }
}

function renewPartList() {
    participants = api.getParticipantsInfo();
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

function checkDeviceinList(list, labelOrId) {


    for (var type in list) {
        for (var dev of list[type]) {
            if (dev.deviceId === labelOrId) {
                return dev.deviceId
            }
            if (dev.label === labelOrId) {
                return dev.deviceId;
            }

        }
    }
    return false;
}

function eventIsMuted(e) {
    isMuted = e.muted;
}

function eventIsVideoMuted(e) {
    isVideoMuted = e.muted;
}


async function pauseConference() {
    api.executeCommand('displayName', '(Away) ' + displayName);
    api.removeListener('audioMuteStatusChanged', eventIsMuted);
    api.removeListener('videoMuteStatusChanged', eventIsVideoMuted);
    api.isAudioMuted().then(muted => {
        if (!muted) {
            api.executeCommand('toggleAudio');
        }
    });
    api.isVideoMuted().then(muted => {
        if (!muted) {
            api.executeCommand('toggleVideo');
        }
    });
    api.executeCommand('avatarUrl', 'https://avatars0.githubusercontent.com/u/3671647');
}

async function playConference() {
    api.executeCommand('displayName', displayName);
    if (!isMuted) {
        api.executeCommand('toggleAudio');
    }
    if (!isVideoMuted) {
        api.executeCommand('toggleVideo');
    }
    api.addListener('audioMuteStatusChanged', eventIsMuted);
    api.addListener('videoMuteStatusChanged', eventIsVideoMuted);
    api.executeCommand('avatarUrl', avatarUrl);
}

export {initJitsi, hangup, askHangup, checkDeviceinList, pauseConference, playConference}
