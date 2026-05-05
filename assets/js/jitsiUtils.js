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
import {choosenLabelFull} from "./cameraUtils";
import {micLabelFull} from "./audioUtils";

global.$ = global.jQuery = $;

import ('jquery-confirm');

export class JitsiUtils {
    domain = null;
    api = null;
    participants = [];

    microphoneLabel = null;
    cameraLable = null;
    displayName = null;
    isMuted = null;
    isCameraOn = null;
    avatarUrl = null;
    pauseController;
    jitsiErrorController;
    myId = null;
    roomName = null;
    isBreakout = null;
    conferenceUtils;
    options = {};
    callbackFktForAskHangup;
    iframeIsPause = false;
    muteTimer = null;

    constructor(options, domain, videoOn, videoId, micId, askForHangupCallbackFkt) {
        this.microphoneLabel = micId;
        this.cameraLable = videoId;
        this.isCameraOn = videoOn;
        this.domain = domain;
        this.options = options;
        this.avatarUrl = null;
        this.displayname = null;
        this.api = new JitsiMeetExternalAPI(this.domain, this.options);
        this.conferenceUtils = new ConferenceUtils(this.api);
        this.conferenceUtils.initConferencePreJoin();
        this.initJitsi();
        this.callbackFktForAskHangup = askForHangupCallbackFkt;
        this.initPlayPause();

    }


    initJitsi() {


        if (typeof this.options.userInfo.avatarUrl !== 'undefined') {
            this.avatarUrl = this.options.userInfo.avatarUrl;
        }
        if (typeof options.userInfo.displayName !== 'undefined') {
            this.displayName = this.options.userInfo.displayName;
        }
        this.renewPartList()

        this.api.addListener('participantJoined', (id, name) => {
            this.renewPartList()
        });
        this.api.addListener('chatUpdated', function (e) {
            if (e.isOpen == true) {
                document.querySelector('#logo_image').classList.add('transparent');
            } else {
                document.querySelector('#logo_image').classList.remove('transparent');
            }

        });

        this.api.addListener('readyToClose', (e) => {
            leaveMeeting();
            initStarSend();
            this.api = null;
            this.endMeeting();
            var timeout =  150000;
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

        this.api.addListener('toolbarButtonClicked', (e) => {
            if (e.key === 'hangup') {
                console.log('hangup klicked');
                if (this.callbackFktForAskHangup){
                    this.callbackFktForAskHangup();
                }

            }
        });

        this.api.addListener('videoConferenceJoined', (e) => {
            enterMeeting();
            initStartWhiteboard();
            this.conferenceUtils.initConferencePostJoin();
            if (avatarUrl != '') {
                this.api.executeCommand('avatarUrl', this.avatarUrl);
            }
            window.onbeforeunload = function () {
                return 'Do you really want to leave this conference';
            };
            this.myId = e.id;
            this.roomName = e.roomName;
            this.isBreakout = e.breakoutRoom;

            if (setTileview === 1) {
                this.api.executeCommand('setTileView', {enabled: true});
            }
            if (avatarUrl !== '') {
                this.api.executeCommand('avatarUrl', avatarUrl);
            }
            if (setParticipantsPane === 1) {
                this.api.executeCommand('toggleParticipantsPane', {enabled: true});
            }

            this.api.addListener('participantKickedOut', (e) => {
                if (e.kicked.local) {
                    leaveMeeting();
                    initStarSend();
                    this.api = null;
                }
            });

            this.api.getAvailableDevices().then((devices) => {
                if (this.cameraLable) {
                    this.api.setVideoInputDevice(this.cameraLable);

                }
                if (this.microphoneLabel) {
                    this.api.setAudioInputDevice(this.microphoneLabel);

                }
                if (this.isCameraOn){
                    this.swithCameraOn(this.isCameraOn);
                }

            });
            if (this.isCameraOn){
                this.swithCameraOn(this.isCameraOn);
            }


        })
        const iframe = document.querySelector('iframe');
        iframe.style.height = '100%';
        window.scrollTo(0, 1);
    }

    renewPartList() {
        this.participants = this.api.getParticipantsInfo();
    }

    endMeeting() {
        if (!this.api) {
            return false;
        }
        this.kickAlUsers();//this is only for lobymoderator use and should not be used from participant

        this.participants = this.api.getParticipantsInfo();
        this.hangup();
        return 0;
    }

    kickAlUsers() {//this only worksif the user moderator right in the jitsi meet confernece. otherwise this function will fail
        this.participants = this.api.getParticipantsInfo();
        for (var i = 0; i < this.participants.length; i++) {
            if (this.api) {
                this.api.executeCommand('kickParticipant', this.participants[i].participantId);
            }

        }
    }

    hangup() {
        if (this.api) {
            this.api.executeCommand('hangup');
        }
    }


    swithCameraOn(videoOn) {
        if (videoOn === true) {
            var muted =
                this.api.isCameraOn().then(muted => {
                    if (muted) {
                        this.api.executeCommand('toggleVideo');
                    }
                });
        } else {
            this.api.isCameraOn().then(muted => {
                if (!muted) {
                    this.api.executeCommand('toggleVideo');
                }

            });
        }
    }

    checkDeviceinList(list, labelOrId) {


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

    eventIsMuted(e) {
        this.isMuted = e.muted;
    }

    eventisCameraOn(e) {
        this.isCameraOn = e.muted;
    }

    initPlayPause() {
        showPlayPause();
        this.initMessengerListener();
    }

    initMessengerListener() {
        const self = this;
        window.addEventListener('message', (e) => {

            const decoded = e.data;


            if (typeof decoded.scope !== 'undefined' && decoded.scope === "jitsi-admin-iframe") {
                if (decoded.type === 'pauseIframe') {
                    self.pauseConference();
                } else if (decoded.type === 'playIframe') {
                    self.playConference();
                } else if (decoded.type === 'pleaseClose') {
                    if (this.callbackFktForAskHangup){
                        this.callbackFktForAskHangup();
                    }
                }
            }
        });

        this.api.addListener('participantJoined', participant => {
            // update mute state for newly joined participant
            self.updateMuteState(participant.id);
        });

        this.api.isAudioMuted().then(muted => {
            this.isVideoMuted = muted
        });
        this.api.isVideoMuted().then(muted => {
            this.isMuted = muted;
        });

        this.api.addListener('audioMuteStatusChanged', data => {
            // update mute state for newly joined participant
            this.isMuted = data['muted'];

        });

        this.api.addListener('videoMuteStatusChanged', data => {
            this.isVideoMuted = data['muted'];
        });

    }

    pauseConference() {
        this.iframeIsPause = true;
        this.changeMicStart();
        this.api.executeCommand('displayName', '(Away) ' + this.displayName);
        this.api.executeCommand('avatarUrl', 'https://www3.h2-invent.com/user_away.webp');
        this.updateMuteStateForAll();
    }


    playConference() {
        this.iframeIsPause = false;
        this.changeMicStart();
        this.api.executeCommand('displayName', this.displayName);


        this.api.executeCommand('avatarUrl', this.avatarUrl);
        this.updateMuteStateForAll();
    }

    updateMuteState(participantId) {
        console.log(`participant ${participantId} muted=${this.iframeIsPause}`);
        this.api.executeCommand('setParticipantVolume', participantId, this.iframeIsPause ? 0 : 1);
    }


    updateMuteStateForAll() {
        this.api.getRoomsInfo().then(event => {
            event.rooms.forEach(room => {
                if (!this.isCurrentRoom(room)) {
                    return;
                }
                room.participants.forEach(participant => {
                    if (participant.id !== this.myId) {
                        this.updateMuteState(participant.id);
                    }
                })
            })
        });
    }

    isCurrentRoom(room) {
        if (!this.isBreakout && room.isMainRoom) {
            return true;
        } else {
            return room.jid.startWith(`${roomName}@`);
        }
    }

    changeMicStart() {
        if (this.muteTimer) {
            clearTimeout(this.muteTimer);
            this.muteTimer = null;
        }

        this.muteTimer = setTimeout(this.changeMicAndCamStatus, 1000, this);
    }

    changeMicAndCamStatus(ele) {

        if (ele.iframeIsPause) {
            ele.isMutedBeforePause = ele.isMuted;
            ele.isVideoMutedBefore = ele.isVideoMuted;
        }

        if ((!ele.isMuted && ele.iframeIsPause) || (ele.isMuted && !ele.iframeIsPause && !ele.isMutedBeforePause)) {
            ele.api.executeCommand('toggleAudio');
        }


        if ((!ele.isVideoMuted && ele.iframeIsPause) || (!ele.iframeIsPause && ele.isVideoMuted && !ele.isVideoMutedBefore)) {
            ele.api.executeCommand('toggleVideo');
        }
    }
}




