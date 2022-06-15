import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'
import {setSnackbar} from './myToastr'
import notificationSound from '../sound/notification.mp3'
import {initAdhocMeeting} from './adhoc'
var audio = new Audio(notificationSound);
import {TabUtils} from './tabBroadcast'

import {initLayzLoading} from './lazyLoading'
function initGenerell() {
    Push.Permission.request();
    initDarkmodeSwitch();
    initLayzLoading();
    openBlankTarget(blankTarget);
    initAdhocMeeting(confirmTitle, confirmCancel, confirmOk);
}
function openBlankTarget(targets) {
    targets.forEach(function (value,i) {
        window.open(value);
    })
}

export {initGenerell}