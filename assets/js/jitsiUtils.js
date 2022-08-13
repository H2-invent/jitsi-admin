/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';
import {close} from './moderatorIframe'

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import ('jquery-confirm');
var api;
var participants;

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initJitsi(options, domain, titelL, okL, cancelL) {
    title = titelL;
    cancel = cancelL;
    ok = okL;
    api = new JitsiMeetExternalAPI(domain, options);
    renewPartList()

    api.addListener('participantJoined', function (id, name) {
        renewPartList()
    });
    api.addListener('chatUpdated', function (e) {
        if (e.isOpen == true) {
            document.querySelector('#logo_image').classList.add('transparent');
        } else {
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
    api.addListener('toolbarButtonClicked', function (e) {

        if (e.key === 'hangup') {
            askHangup()
        }

    });
    api.addListener('videoConferenceJoined', function (e) {
        $('#closeSecure').removeClass('d-none').click(function (e) {
            e.preventDefault();
            var url = $(this).prop('href');
            var text = $(this).data('text');
            $.confirm({
                title: title,
                content: text,
                theme: 'material',
                buttons: {
                    confirm: {
                        text: ok, // text for button
                        btnClass: 'btn-outline-danger btn', // class for the button
                        action: function () {
                            endMeeting();
                            $.getJSON(url);
                        },
                    },
                    cancel: {
                        text: cancel, // text for button
                        btnClass: 'btn-outline-primary btn', // class for the button
                    },
                }
            });

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

        $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');


    });

}

function endMeeting() {
    participants = api.getParticipantsInfo();
    for (var i = 0; i < participants.length; i++) {
        api.executeCommand('kickParticipant', participants[i].participantId);
    }
    return 0;
}

function askHangup() {
    if (!api){
        return false;
    }
    $.confirm({
        title:null,
        content: hangupQuestion,
        theme: 'material',
        buttons: {
            confirm: {
                text: hangupText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    api.executeCommand('hangup');
                    close()
                },
            },killAll: {
                text: endMeetingText, // text for button
                btnClass: 'btn-danger btn', // class for the button
                action: function () {
                    endMeeting();
                    $.getJSON(endMeetingUrl);
                    close()
                },
            },

            cancel: {
                text: cancel, // text for button
                btnClass: 'btn-outline-primary btn', // class for the button
            },
        }
    });
    return true;
}

function hangup() {
    api.executeCommand('hangup');
}

function renewPartList() {
    participants = api.getParticipantsInfo();
}


export {initJitsi, hangup, askHangup}
