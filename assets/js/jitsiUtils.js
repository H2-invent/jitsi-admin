/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import stc from 'string-to-color/index';
import {masterNotify} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam, choosenId} from './cameraUtils'
import * as url from "url";

var api;
var participants;

function initJitsi(options, domain) {
    api = new JitsiMeetExternalAPI(domain, options);
    renewPartList()
    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    api.addListener('readyToClose',function (e) {
        endMeeting();
    })
    $('#closeSecure').removeClass('d-none').click(function (e) {
        e.preventDefault();
        endMeeting();
        api.dispose();
        window.location.href='/';
    })
    window.addEventListener('unload', function(e) {
        api.dispose();
    });
}
function endMeeting() {
    participants = api.getParticipantsInfo()
    for (var i = 0; i<participants.length; i++){
        api.executeCommand('kickParticipant',participants[i].participantId);
    }
}

function renewPartList() {
    participants = api.getParticipantsInfo();
}


console.log('handler');
// window.addEventListener("beforeunload", function(event) {
//     $.get(removeUrl)
// });
export {initJitsi}
