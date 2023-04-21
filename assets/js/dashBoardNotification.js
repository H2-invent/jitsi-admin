import $ from "jquery";
import {masterNotify, initNotofication, stopCallerPlay} from './lobbyNotification';

let es;
let topic;

function initDashboardnotification(lTopic) {
    initNotofication();
    topic = lTopic;
    $(document).on('click', '.toast', function () {
        stopCallerPlay();
    })

}

export {initDashboardnotification};
