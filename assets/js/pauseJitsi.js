/*
 * Welcome to your app's main JavaScript file!
 *
 */

import {api} from "./jitsiUtils";
import {showPlayPause} from "./moderatorIframe";

class jitsiController {
    api = null;
    displayName = null;
    avatarUrl = null;
    isMuted = false;
    isVideoMuted = false;
    isMutedBeforePause = false;
    isVideoMutedBefore = false;
    participants = {};

    iframeIsPause = false;

    myId = null;
    roomName = null;
    isBreakout = null;
    muteTimer = null;

    constructor(api, displayName, avatarUrl, myId, roomName, isBreakout) {
        this.api = api;
        this.displayName = displayName;
        this.avatarUrl = avatarUrl;
        this.myId = myId;
        this.roomName = roomName;
        this.isBreakout = isBreakout;
        showPlayPause();
        this.initMessengerListener();
    }

    initMessengerListener() {
        const self = this;
        window.addEventListener('message', function (e) {

            const decoded = JSON.parse(e.data);
            if (typeof decoded.scope !== 'undefined' && decoded.scope === "jitsi-admin-iframe") {
                if (decoded.type === 'pauseIframe') {
                    self.pauseConference();
                } else if (decoded.type === 'playIframe') {
                    self.playConference();
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
        this.api.executeCommand('avatarUrl', 'https://avatars0.githubusercontent.com/u/3671647');
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


export {jitsiController}
