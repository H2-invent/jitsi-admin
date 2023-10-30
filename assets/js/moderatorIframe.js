global.frameId = null;
var blockPause = null;
var showBlockPause = false;
var jitsiC = null;

function initModeratorIframe(closeFkt, jitsiController = null) {
    jitsiC = jitsiController;
    window.addEventListener('message', function (e) {

        const decoded = JSON.parse(e.data);
        if (typeof decoded.scope !== 'undefined' && decoded.scope == "jitsi-admin-iframe") {
            window.parent.postMessage(JSON.stringify({type: 'ack', messageId: decoded.messageId}), '*');
            if (decoded.type === 'init') {
                frameId = decoded.frameId;
                if (showBlockPause) {
                    showPlayPause();
                }
            } else if (decoded.type === 'pleaseClose') {
                if (typeof decoded.frameId !== 'undefined') {
                    frameId = decoded.frameId;
                }
                //send stop closing the multiframe automatically the closing function is handled by the multiframe itself
                //
                stopclosingMe(frameId);
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
export function stopclosingMe(frameIdTmp) {
    if (inIframe()) {
        var id = frameIdTmp ? frameIdTmp : frameId
        if (id) {
            const message = JSON.stringify({
                type: 'stopClosingMe',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        }
    }
}

function showPlayPause() {
    if (inIframe()) {
        var id = frameId
        if (id) {
            const message = JSON.stringify({
                type: 'showPlayPause',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        } else {
            showBlockPause = true;
        }
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
