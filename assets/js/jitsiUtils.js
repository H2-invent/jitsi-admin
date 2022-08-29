/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';
import {closeIframe} from "./lobbyModerator";

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;

import ('jquery-confirm');
var api;
var participants;

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";
var microphoneLabel = null;
var cameraLable = null;
function initJitsi(options, domain, titelL, okL, cancelL,videoOn, videoId, micId) {
    title = titelL;
    cancel = cancelL;
    ok = okL;
    microphoneLabel = micId;
    cameraLable = videoId;
    api = new JitsiMeetExternalAPI(domain, options);
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
        endMeeting();
        if (window.opener == null) {
            setTimeout(function () {
                window.location.href = data.url;
            }, data.timeout)
        } else {
            setTimeout(function () {
                window.close();
            }, data.timeout)
        }
    });
    api.addListener('toolbarButtonClicked', function (e) {

        if (e.key === 'hangup') {
            askHangup()
        }

    });
    api.addListener('videoConferenceJoined', function (e) {
        $('#closeSecure').removeClass('d-none').click(function (e) {
            e.preventDefault();
            var url = $(this).prop('href');
            var text = $(this).data('text');
            $.confirm({
                title: title,
                content: text,
                theme: 'material',
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
            api.setVideoInputDevice(cameraLable);
            api.setAudioInputDevice(microphoneLabel);
            swithCameraOn(videoOn);
        });
        swithCameraOn(videoOn);



        $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');


    });

}

function endMeeting() {
    participants = api.getParticipantsInfo();
    for (var i = 0; i < participants.length; i++) {
        api.executeCommand('kickParticipant', participants[i].participantId);
    }
    return 0;
}

function askHangup() {
    if (!api){
        return false;
    }
    $.confirm({
        title:null,
        content: hangupQuestion,
        theme: 'material',
        buttons: {
            confirm: {
                text: hangupText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    api.executeCommand('hangup');
                    closeIframe();
                },
            },killAll: {
                text: endMeetingText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    endMeeting();
                    $.getJSON(endMeetingUrl);
                    closeIframe();
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
    api.executeCommand('hangup');
}

function renewPartList() {
    participants = api.getParticipantsInfo();
}

function swithCameraOn(videoOn) {
    if (videoOn === 1){
        var muted =
            api.isVideoMuted().then(muted => {
                console.log(muted)
                if (muted){
                    api.executeCommand('toggleVideo');
                }
            });
    }else {
        api.isVideoMuted().then(muted => {
            if (!muted){
                api.executeCommand('toggleVideo');
            }

        });
    }
}
export {initJitsi, hangup, askHangup}
