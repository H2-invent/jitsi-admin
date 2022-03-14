/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
var api;
var participants;

function initJitsi(options, domain) {
    api = new JitsiMeetExternalAPI(domain, options);
    renewPartList()
    if (typeof avatarUrl !== 'undefined') {
        api.executeCommand('avatarUrl', avatarUrl);
    }
    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    // api.addListener('readyToClose', function (e) {
    //     endMeeting();
    // })
    api.addListener('videoConferenceJoined', function (e) {
        $('#closeSecure').removeClass('d-none').click(function (e) {
            e.preventDefault();
            endMeeting();
            $.getJSON(($(this).attr('href')));
        })
        $('#sliderTop').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');

    })

}

function endMeeting() {
    participants = api.getParticipantsInfo();
    for (var i = 0; i < participants.length; i++) {
        api.executeCommand('kickParticipant', participants[i].participantId);
    }
    return 0;
}

function hangup() {
    api.executeCommand('hangup');
}

function renewPartList() {
    participants = api.getParticipantsInfo();
}


export {initJitsi, hangup}
