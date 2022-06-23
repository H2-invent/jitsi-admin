import $ from 'jquery';
import * as mdb from 'mdb-ui-kit'; // lib
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
    $("body").keypress(function (e) {
        var pressed = e.which
        if (pressed === 49) {//1 is pressed
            $('#ex1-tab-1-tab').trigger('click');
        } else if (pressed === 50) {//2 is pressed
            $('#ex1-tab-3-tab').trigger('click');
        } else if (pressed === 51) {//3 is pressed
            $('#ex1-tab-2-tab').trigger('click');
        }else if(pressed ===110){
            $('#createNewConference').trigger('click');
        }else if(pressed ===97){
            const myModalEl = document.getElementById('modalAdressbook');
            if (!myModalEl.classList.contains('show')){
                const modal = new mdb.Modal(myModalEl)
                modal.show();
            }
            $('#home-tab').trigger('click');

        }else if(pressed ===103){
            const myModalEl = document.getElementById('modalAdressbook');
            if (!myModalEl.classList.contains('show')){
                const modal = new mdb.Modal(myModalEl)
                modal.show();
            }
            $('#profile-tab').trigger('click');
        }
    });
}

function openBlankTarget(targets) {
    targets.forEach(function (value, i) {
        window.open(value);
    })
}

export {initGenerell}