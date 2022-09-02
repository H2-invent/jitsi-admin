import {enterMeeting, initWebsocket, leaveMeeting} from "./websocket";
import {echoOff} from "./audioUtils";
import {stopWebcam} from "./cameraUtils";
import {close, inIframe, initModeratorIframe} from "./moderatorIframe";
import {initStarSend} from "./endModal";
import {initStartWhiteboard} from "./startWhiteboard";

var frameId;
var api = new JitsiMeetExternalAPI(domain, options);

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
    window.onbeforeunload = function (e) {
        e.preventDefault();
        e.stopImmediatePropagation();
        return closeTabText;
    }
    api.addListener('videoConferenceLeft', function (e) {
        leaveMeeting();
        initStarSend();
    });

    if (setTileview === 1) {
        api.executeCommand('setTileView', {enabled: true});
    }
    if (avatarUrl !== '') {
        api.executeCommand('avatarUrl', avatarUrl);
    }
    if (setParticipantsPane === 1) {
        api.executeCommand('toggleParticipantsPane', {enabled: true});
    }
})

var iframe = document.querySelector('#jitsiConferenceFrame0');
iframe.style.height = '100%';

window.addEventListener('message', function (e) {
    // add here more commands up to now only close is defined.
    const data = e.data;
    const decoded = JSON.parse(data);
    if (decoded.type === 'pleaseClose') {
        console.log('we are asked to close');
        if (api) {
            api.executeCommand('hangup')
        }
        frameId = decoded.frameId;
        close(frameId);
        const message = JSON.stringify({
            type: 'closeMe',
            frameId: frameId
        });
        window.parent.postMessage(message, '*');
    } else if (decoded.type === 'init') {
        frameId = decoded.frameId;
    }
});


window.onmessage = function (event) {
    if (event.data === "jitsi-closed") {
        window.onbeforeunload = null;
        window.close();
    }
};

function docReady(fn) {
    // see if DOM is already available
    if (document.readyState === "complete" || document.readyState === "interactive") {
        // call on next available tick
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}

function checkClose() {
    api.executeCommand('hangup');
    leaveMeeting();
    close(frameId)// sende ein SendME an das Parent-Elemen, damit das Iframe geschlossen wird
}

docReady(function () {
    initModeratorIframe(checkClose);
    initWebsocket(websocketTopics);
});
