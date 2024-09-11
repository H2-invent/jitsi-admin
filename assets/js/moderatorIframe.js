//this is the file to manage the comandy which are sended to the iframe that includes a confernece. here are all functions bundled which are import for a conference iframe session. T
// this file is independent if jitsi or livekit and need to be called from all types of conferences
var blockPause = null;
var showBlockPause = false;

function initModeratorIframe(closeFkt) {
    window.addEventListener('message', function (e) {
        const decoded = e.data;
        if (typeof decoded.scope !== 'undefined' && decoded.scope == "jitsi-admin-iframe") {
            window.parent.postMessage(JSON.stringify({type: 'ack', messageId: decoded.messageId}), '*');
            if (decoded.type === 'init') {

                if (showBlockPause) {
                    showPlayPause();
                }
            } else if (decoded.type === 'pleaseClose') {

                stopclosingMe();
                closeFkt();
            }
        }
    });
    var floatingTag = document.getElementById('tagContent');
    if (!floatingTag) {
        return;
    }
    var color = floatingTag.style.backgroundColor;
    sendBorderColorToMultiframe(color);
}

function close() {
    if (inIframe()) {

            const message = JSON.stringify({
                type: 'closeMe',
            });
            window.parent.postMessage(message, '*');
    }
}
export function stopclosingMe() {
    if (inIframe()) {

            const message = JSON.stringify({
                type: 'stopClosingMe',
            });
            window.parent.postMessage(message, '*');

    }
}

function showPlayPause() {
    if (inIframe()) {

            const message = JSON.stringify({
                type: 'showPlayPause',
            });
            window.parent.postMessage(message, '*');
        }
    }

function sendBorderColorToMultiframe(color) {
    if (inIframe()) {

            const message = JSON.stringify({
                type: 'colorBorder',
                color: color,
                url: window.location.href,
            });
            window.parent.postMessage(message, '*');

    }
}

function inIframe() {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}

export {initModeratorIframe, close, showPlayPause, inIframe,sendBorderColorToMultiframe}
