import {close, inIframe, initModeratorIframe, showPlayPause} from "./moderatorIframe";
import {initStarSend} from "./endModal";
import ClipboardJS from 'clipboard'

var frameId;

window.addEventListener('message', function (e) {
    // add here more commands up to now only close is defined.
    const data = e.data;
    const decoded = JSON.parse(data);
    if (decoded.type === 'pleaseClose') {
            close(frameId);
    } else if (decoded.type === 'init') {
        frameId = decoded.frameId;
    }
});


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
    initModeratorIframe(dummy);
});

function dummy() {

}