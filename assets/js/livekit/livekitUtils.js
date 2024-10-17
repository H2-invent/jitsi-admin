import {initSocialIcons} from "../createSocialButtons";
import {ToolbarUtils} from "../ToolbarUtils";
import {livekitApi} from "./main";
import {enterMeeting, leaveMeeting} from "../websocket";
import {initStartWhiteboard} from "../startWhiteboard";
import {showPlayPause} from "../moderatorIframe";
import {initStarSend} from "../endModal";

export class LivekitUtils {
    conferenceRunning = false;
    conferencePaused = false;
    micLastStateOn = false;
    cameraLastStateON = false;

    constructor(parent, url, videoOn = null, cameralabel = null, miclabel = null) {
        this.videoOn = videoOn;
        this.cameraLabel = cameralabel;
        this.miclabel = miclabel;
        this.api = new livekitApi(parent, url);
        this.toolbar = new ToolbarUtils();
        initSocialIcons(this.changeCamera.bind(this));
        this.initGeneralIncommingmessages();
        this.conferencePaused = false;
        this.micLastStateOn = true;
        this.cameraLastStateON = true;
        this.api.addEventListener('LocalParticipantConnected', () => {
            enterMeeting();
            initStartWhiteboard();
            showPlayPause();
            initSocialIcons(changeCamera.bind(this));
            this.initChatToggle();
            this.conferenceRunning = true;
            this.initMicAndCamera();
            window.onbeforeunload = function (e) {
                e.preventDefault();
                e.stopImmediatePropagation();
                return closeTabText;
            }
        });

        this.api.addEventListener('LocalParticipantDisconnected', () => {
            leaveMeeting();
            initStarSend();
            console.log('The user left the meeting');
            this.conferenceRunning = false;
        });
        this.api.addEventListener('mousemove', () => {
            this.toolbar.sidebarAction();
        });
        this.api.addEventListener('TrackMuted', (e) => {
            console.log(e);
            switch (e.detail.track) {
                case 'microphone':
                    if (!this.conferencePaused) {
                        this.micLastStateOn = false;
                    }
                    break;
                case 'camera':
                    if (!this.conferencePaused) {
                        this.cameraLastStateON = false;
                    }
                    break;
            }
        });
        this.api.addEventListener('TrackMuted', (e) => {
            console.log(e);
            switch (e.detail.track) {
                case 'microphone':
                    if (!this.conferencePaused) {
                        this.micLastStateOn = false;
                    }
                    break;
                case 'camera':
                    if (!this.conferencePaused) {
                        this.cameraLastStateON = false;
                    }
                    break;
            }
        });
        this.api.addEventListener('ChatMessageReceived', (e) => {
            const data = e.detail;
            console.log(data);
            if (data.numberUnreadMessages > 0) {
                this.filterDot.classList.remove('d-none');
                this.filterDot.textContent = data.numberUnreadMessages;
                this.chatBtn.style.setProperty('background-color', '#2561ef', 'important');
                this.chatBtn.style.color = '#ffffff';
            } else {
                this.filterDot.classList.add('d-none');
                this.chatBtn.style.removeProperty('backgropm -und-color');
                this.chatBtn.style.removeProperty('color');
            }
        });

        function changeCamera(cameraLabel) {
            console.log(`change camera to ${cameraLabel}`);
            this.setCameraByLabel(cameraLabel);
        }
    }

    initGeneralIncommingmessages() {
        window.addEventListener('message', (e) => {
            const decoded = e.data;

            if (typeof decoded.scope !== 'undefined' && decoded.scope === "jitsi-admin-iframe") {
                switch (decoded.type) {
                    case 'pauseIframe':
                        this.conferencePaused = true;
                        this.toggleMic(false);
                        this.toggleCamera(false);
                        this.setNameWithPrefix('(Away) ' + displayName);
                        this.setAvatarUrl('https://www3.h2-invent.com/user_away.webp');
                        this.setRemoteParticipantsVolume(0);
                        break;
                    case 'playIframe':
                        this.conferencePaused = false;
                        this.toggleMic(this.micLastStateOn);
                        this.toggleCamera(this.cameraLastStateON);
                        this.setNameWithPrefix(displayName);
                        this.setAvatarUrl(avatarUrl)
                        this.setRemoteParticipantsVolume(100);
                        break;
                    // Weitere Fälle können hier hinzugefügt werden
                    default:
                        console.log(`Unbekannter Nachrichtentyp: ${decoded.type}`);
                        break;
                }
            }
        });
    }


    changeCamera(cameraLabel) {
        console.log(cameraLabel);
        this.setCameraByLabel(cameraLabel);
    }

    toggleMic(enable) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setMicrophoneEnabled',
            {enabled: enable}
        )

    }

    toggleCamera(enable) {

        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setCameraEnabled',
            {enabled: enable}
        )
    }

    setNameWithPrefix(name) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setName',
            {
                name: name
            }
        )
    }

    setAvatarUrl(url) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setAvatarUrl',
            {
                url: url
            }
        )
    }

    hangup() {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'disconnect'
        )
        return true;
    }

    initMicAndCamera() {
        if (this.cameraLabel) {
            this.setCameraByLabel(this.cameraLabel);
        }
        if (this.miclabel) {
            this.setMicByLabel(this.miclabel);
        }
        if (this.videoOn !== null) {
            if (this.videoOn) {
                this.toggleCamera(true);
            } else {
                this.toggleCamera(false);
            }
        }
    }

    setCameraByLabel(label) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'switchActiveDevice',
            {
                kind: 'videoinput',
                label: label,
            },
        )
        return true;
    }

    setMicByLabel(label) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'switchActiveDevice',
            {
                kind: 'audioinput',
                label: label,
            },
        )
        return true;
    }

    setRemoteParticipantsVolume(volume) {
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setRemoteParticipantsVolume',
            {
                setRemoteParticipantsVolume: volume,
            },
        )
        return true;
    }
    initChatToggle() {
        this.chatBtn = document.getElementById('externalChat');
        if (!this.chatBtn) {
            return false;
        }
        this.filterDot = this.chatBtn.querySelector('.filter-dot');

        if (this.chatBtn) {
            this.chatBtn.addEventListener('click', ()=> {
               this.api.sendMessageToIframe('LocalParticipant','toggleChat');
                this.filterDot.classList.add('d-none');
                this.chatBtn.style.removeProperty('background-color');
                this.chatBtn.style.removeProperty('color');
            })

        }
    }

}