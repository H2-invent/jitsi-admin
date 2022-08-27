

let frameId = null;
function initModeratorIframe(closeFkt) {

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
    if (inIframe()){
        // document.querySelector('.footer').remove();
        // document.querySelector('body').appendChild(document.getElementById('logo_image'));
        // document.getElementById('logo_image').classList.add('d-none');
        // document.querySelector('.navigation').remove();
    }
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
function inIframe () {
    try {
        return window.self !== window.top;
    } catch (e) {
        return true;
    }
}
export {initModeratorIframe,close, inIframe}
