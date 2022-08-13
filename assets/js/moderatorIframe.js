import {initWebcam, choosenId, stopWebcam} from './cameraUtils'
import {initAUdio, micId, audioId, echoOff} from './audioUtils'
import {initJitsi, hangup, askHangup} from './jitsiUtils'



function initModeratorIframe(closeFkt) {
    let frameId = null;
    window.addEventListener('message', function (e) {
        const decoded = JSON.parse(e.data);
         if (decoded.type === 'init') {
            frameId = decoded.frameId;
        }else if(decoded.type === 'close'){
             if (typeof decoded.frameId !== 'undefined'){
                 frameId = decoded.frameId;
             }
             closeFkt();
        }
    });
}

function close(frameIdTmp) {
    var id = frameIdTmp?frameIdTmp:frameId
    if (id){
        const message = JSON.stringify({
            type: 'close',
            frameId: id
        });
        window.parent.postMessage(message, '*');
    }

}
export {initModeratorIframe,close}
