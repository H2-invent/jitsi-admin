import {initSocialIcons} from "../createSocialButtons";
import {ToolbarUtils} from "../ToolbarUtils";
import {livekitApi} from "./main";
import {enterMeeting, leaveMeeting} from "../websocket";
import {initStartWhiteboard} from "../startWhiteboard";
import {showPlayPause} from "../moderatorIframe";
import {initStarSend} from "../endModal";

export class LivekitUtils {
    conferenceRunning = false;

    constructor(parent, url, videoOn = null, cameralabel = null, miclabel = null) {
        this.videoOn = videoOn;
        this.cameraLabel = cameralabel;
        this.miclabel = miclabel;
        this.api = new livekitApi(parent, url);
        this.toolbar = new ToolbarUtils();
        this.initSidebarMove();
        initSocialIcons(this.changeCamera.bind(this));
        this.initGeneralIncommingmessages();
        this.api.addEventListener('LocalParticipantConnected', () => {
            enterMeeting();
            initStartWhiteboard();
            showPlayPause();
            initSocialIcons(changeCamera.bind(this));
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

        function changeCamera(cameraLabel) {
            console.log(`change camera to ${cameraLabel}`);
            //todo hier camera setzen nanch label
        }
    }

    initGeneralIncommingmessages() {
        window.addEventListener('message', (e) => {
            const decoded = e.data;

            if (typeof decoded.scope !== 'undefined' && decoded.scope === "jitsi-admin-iframe") {
                switch (decoded.type) {
                    case 'pauseIframe':
                        this.toggleMic(false);
                        this.toggleCamera(false);
                        this.setNameWithPrefix('(Away) ' + displayName);
                        this.setAvatarUrl('https://www3.h2-invent.com/user_away.webp');
                        this.setRemoteParticipantsVolume(0);
                        break;
                    case 'playIframe':
                        this.toggleMic(true);
                        this.toggleCamera(true);
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


    initSidebarMove() {

        this.api.iframe.addEventListener("mouseover", () => {
            this.toolbar.sidebarAction();
        });
        this.api.addEventListener("touchstart", () => {
            this.toolbar.sidebarAction();
        });
    }

    changeCamera(cameraLabel) {
        console.log(cameraLabel);
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
        if (this.videoOn !== null){
            if(this.videoOn){
                this.toggleCamera(true);
            }else {
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
    setRemoteParticipantsVolume(volume){
        this.api.sendMessageToIframe(
            'LocalParticipant',
            'setRemoteParticipantsVolume',
            {
                setRemoteParticipantsVolume: volume,
            },
        )
        return true;
    }

}