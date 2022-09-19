
global.frameId = null;
function initModeratorIframe(closeFkt) {

    window.addEventListener('message', function (e) {
        const decoded = JSON.parse(e.data);
         if (decoded.type === 'init') {
            frameId = decoded.frameId;
        }else if(decoded.type === 'pleaseClose'){
             if (typeof decoded.frameId !== 'undefined'){
                 frameId = decoded.frameId;
             }
             closeFkt();
        }
    });
}

function close(frameIdTmp) {
    if (inIframe()){
        var id = frameIdTmp?frameIdTmp:frameId
        if (id){
            const message = JSON.stringify({
                type: 'closeMe',
                frameId: id
            });
            window.parent.postMessage(message, '*');
        }
    }
}
function inIframe () {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}
export {initModeratorIframe,close, inIframe}
