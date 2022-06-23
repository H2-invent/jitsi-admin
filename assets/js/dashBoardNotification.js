import $ from "jquery";
import {masterNotify, initNotofication, stopCallerPlay} from './lobbyNotification';

let es;
let topic;

function initDashboardnotification(lTopic) {
    initNotofication();
    topic = lTopic;
    connectES()
    $(document).on('click', '.toast', function () {
        stopCallerPlay();
    })
    setInterval(function () {
        if (es.readyState === 2) {
            connectES();
        }
    },5000);
}

function connectES() {
    es = new EventSource([topic]);
    es.onmessage = e => {
        var data = JSON.parse(e.data)
        masterNotify(data);
    }
}

export {initDashboardnotification};
