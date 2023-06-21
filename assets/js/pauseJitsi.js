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

    participants = {};

    iframeIsPause = false;

    myId = null;
    roomName = null;
    isBreakout = null;

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

    }

    pauseConference() {
        this.iframeIsPause = true;
        this.changeMicAndCamStatus();
        this.api.executeCommand('displayName', '(Away) ' + this.displayName);
        this.api.executeCommand('avatarUrl', 'https://avatars0.githubusercontent.com/u/3671647');
        this.updateMuteStateForAll();
    }


    playConference() {
        this.iframeIsPause = false;
        this.api.executeCommand('displayName', this.displayName);
        this.changeMicAndCamStatus();

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

    changeMicAndCamStatus() {
        this.api.isAudioMuted().then(muted => {

            if (!muted && this.iframeIsPause) {
                this.isMuted = muted;
                this.api.executeCommand('toggleAudio');
            } else if (muted && !this.iframeIsPause && !this.isMuted) {
                this.api.executeCommand('toggleAudio');
            }
        });
        this.api.isVideoMuted().then(muted => {

            if (!muted && this.iframeIsPause) {
                this.isVideoMuted = muted;
                this.api.executeCommand('toggleVideo');
            } else if (muted && !this.iframeIsPause && !this.isVideoMuted) {
                this.api.executeCommand('toggleVideo');
            }
        });
    }
}


export {jitsiController}
