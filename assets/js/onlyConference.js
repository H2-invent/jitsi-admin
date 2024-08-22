import {enterMeeting, initWebsocket, leaveMeeting} from "./websocket";
import {close, inIframe, initModeratorIframe, showPlayPause} from "./moderatorIframe";
import {initStarSend} from "./endModal";
import {initStartWhiteboard} from "./startWhiteboard";
import * as mdb from 'mdb-ui-kit'; // lib
import ClipboardJS from 'clipboard'
import {initStartIframe} from "./createConference";
import {jitsiController} from "./pauseJitsi";
import {jitsiErrorHandling} from "./jitsiErrorHandling";
import {checkFirefox} from "./checkFirefox";
import {ConferenceUtils} from "./ConferenceUtils";
import {JitsiUtils} from "./jitsiUtils";
import {choosenLabelFull, toggle} from "./cameraUtils";
import {micLabelFull} from "./audioUtils";

let api = new JitsiUtils(options, domain, null, null, null, null)

window.addEventListener('message', function (e) {
    // add here more commands up to now only close is defined.
    const decoded = e.data;
    if (decoded.type === 'pleaseClose') {
        if (api) {

            leaveMeeting();
            initStarSend();
            api.hangup();
        } else {
            close();
        }
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



docReady(function () {
    var clipboard = new ClipboardJS('.copyLink');
    initModeratorIframe();
    initWebsocket(websocketTopics);
    checkFirefox();
});
