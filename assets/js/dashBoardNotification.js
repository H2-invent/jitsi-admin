import $ from "jquery";
import {masterNotify, initNotofication,stopCallerPlay} from './lobbyNotification';

function initDashboardnotification(lTopic){
    initNotofication();
    const es = new EventSource([lTopic]);
    es.onmessage = e => {
        var data = JSON.parse(e.data)
        masterNotify(data);
    }
    $(document).on('click','.toast',function () {
        stopCallerPlay();
    })
}

export {initDashboardnotification};
