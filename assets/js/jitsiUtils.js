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

    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    api.addListener('chatUpdated', function (e) {
        if(e.isOpen == true){
            document.querySelector('#logo_image').classList.add('transparent');
        }else {
            document.querySelector('#logo_image').classList.remove('transparent');
        }

    });
    api.addListener('readyToClose', function (e) {
        endMeeting();
        if (window.opener == null) {
            setTimeout(function () {
                window.location.href = data.url;
            }, data.timeout)
        } else {
            setTimeout(function () {
                window.close();
            }, data.timeout)
        }
    });
    api.addListener('videoConferenceJoined', function (e) {
        $('#closeSecure').removeClass('d-none').click(function (e) {
            e.preventDefault();
            endMeeting();
            $.getJSON(($(this).attr('href')));
        })
        if (setTileview === 1) {
            api.executeCommand('setTileView', {enabled: true});
        }
        if (avatarUrl !== '') {
            api.executeCommand('avatarUrl', avatarUrl);
        }
        if (setParticipantsPane === 1) {
            api.executeCommand('toggleParticipantsPane', {enabled: true});
        }

        $('#sliderTop').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');


    });

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
