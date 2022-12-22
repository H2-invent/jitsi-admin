import {pauseConference, playConference} from "./jitsiUtils";

global.frameId = null;
var blockPause = null;

function initModeratorIframe(closeFkt) {

    window.addEventListener('message', function (e) {

        const decoded = JSON.parse(e.data);
        window.parent.postMessage(JSON.stringify({type: 'ack', messageId: decoded.messageId}), '*');
        if (decoded.type === 'init') {
            frameId = decoded.frameId;
        } else if (decoded.type === 'pleaseClose') {
            if (typeof decoded.frameId !== 'undefined') {
                frameId = decoded.frameId;
            }
            closeFkt();
        } else if (decoded.type === 'pauseIframe') {

            if (blockPause!== true){
                blockPause = true;
                pauseConference().then(function () {
                    blockPause = false
                });
            }

        } else if (decoded.type === 'playIframe') {
            if (typeof decoded.frameId !== 'undefined') {
                if (blockPause !== true) {
                    blockPause = true;
                    playConference().then(function () {
                        blockPause = false
                    });
                }

            }
        }
    });
}

function close(frameIdTmp) {
    if (inIframe()) {
        var id = frameIdTmp ? frameIdTmp : frameId
        if (id) {
            const message = JSON.stringify({
                type: 'closeMe',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        }
    }
}

function inIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

export {initModeratorIframe, close, inIframe}
