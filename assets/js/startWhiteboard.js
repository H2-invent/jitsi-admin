import {inIframe} from "./moderatorIframe";
import {sendViaWebsocket} from "./websocket";

export function initStartWhiteboard() {
    document.querySelector('.startWhiteboard').addEventListener('click', function (ev) {
        var moderatorUrl = this.dataset.moderatorurl;
        var url = this.dataset.url;
        if (inIframe()){
            const parentMessage = JSON.stringify({
                type: 'openNewIframe',
                url: moderatorUrl,
                'title': document.title
            });
            window.parent.postMessage(parentMessage, '*');
            var message = {
                room: this.dataset.room,
                url: url,
                title: document.title
            }
            sendViaWebsocket('openNewIframe',JSON.stringify(message));
        }else {
            //todo open new Iframe
        }
    })
}