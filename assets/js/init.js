import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'
import {setSnackbar} from './myToastr'
import notificationSound from '../sound/notification.mp3'

var audio = new Audio(notificationSound);
import {TabUtils} from './tabBroadcast'

import {initLayzLoading} from './lazyLoading'
function initGenerell() {
    Push.Permission.request();
    initDarkmodeSwitch();
    initLayzLoading();
    openBlankTarget(blankTarget);
}
function openBlankTarget(targets) {
    targets.forEach(function (value,i) {
        window.open(value);
    })
}

export {initGenerell}