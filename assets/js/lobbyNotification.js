import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'

function notifymoderator(data) {
    Push.Permission.request();
    Push.create(data.title, {
        body: data.message,
        icon: '/favicon.ico',
        link: 'test',
        onClick: function (ele) {
            window.focus();
            this.close();
        }
    });
}
function refresh(data){
    var reloadUrl = data.reloadUrl;
    $('#waitingUserWrapper').load(reloadUrl,function () {
        initCircle();
    });
}
export {notifymoderator, refresh}