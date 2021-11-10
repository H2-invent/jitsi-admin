import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'
function notifymoderator(data) {
    var reloadUrl = data.reloadUrl;
    console.log(reloadUrl)
    $('#waitingUserWrapper').load(reloadUrl,function () {
        initCircle();
    });

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
export {notifymoderator}