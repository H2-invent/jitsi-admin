import {inIframe} from "./moderatorIframe";
import {sendViaWebsocket} from "./websocket";
import {createIframe} from "./createConference";

export function initStartWhiteboard() {
    if (!document.querySelector('.startExternalApp')) {
        return null;
    }
    var eles = document.querySelectorAll('.startExternalApp');
    for (var ele of eles) {


        ele.classList.remove('d-none');
        ele.addEventListener('click', function (ev) {
            var selfurl = this.dataset.selfurl;
            var url = this.dataset.url;
            const roomUid = this.dataset.roomUid;
            if (this.dataset.room && url) {
                var message = {
                    room: this.dataset.room,
                    url: url,
                    title: document.title
                }
                sendViaWebsocket('openNewIframe', JSON.stringify(message));
            }

            if (inIframe()) {
                if (selfurl){
                    const parentMessage = JSON.stringify({
                        type: 'openNewIframe',
                        url: selfurl,
                        'title': document.title,
                        roomUid: roomUid,
                    });
                    window.parent.postMessage(parentMessage, '*');
                }
            } else {
                createIframe(selfurl, document.title,  false,'',);
            }
        })
    }
}