import $ from "jquery";
import {masterNotify, initNotofication} from './lobbyNotification';

function initDashboardnotification(lTopic){
    initNotofication();
    const es = new EventSource([lTopic]);
    es.onmessage = e => {
        var data = JSON.parse(e.data)
        masterNotify(data);
    }
}

export {initDashboardnotification};
