import {enterMeeting, initWebsocket, leaveMeeting} from "./websocket";

var frameId;
var api = new JitsiMeetExternalAPI(domain, options);

api.addListener('chatUpdated', function (e) {
    if(e.isOpen == true){
        document.querySelector('#logo_image').classList.add('transparent');
    }else {
        document.querySelector('#logo_image').classList.remove('transparent');
    }

});
api.addListener('videoConferenceJoined', function (e) {
   enterMeeting();
});

api.addListener('videoConferenceJoined', function (e) {
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
    if (decoded.type === 'pleaseClose'){
        console.log('we are asked to close');
        if (api){
            api.executeCommand('hangup')
        }
        leaveMeeting();
        frameId = decoded.frameId;
        const message = JSON.stringify({
            type: 'closeMe',
            frameId: frameId
        });
        window.parent.postMessage(message, '*');
    }else if(decoded.type === 'init'){
        frameId = decoded.frameId;
    }
});



    window.onbeforeunload = function (e) {
    e.preventDefault();
    e.stopImmediatePropagation();
    return closeTabText;
}
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

docReady(function() {
  initWebsocket();
});
