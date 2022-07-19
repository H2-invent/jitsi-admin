import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'
import notificationSound from '../sound/notification.mp3'
import callerSound from '../sound/ringtone.mp3'
import {setSnackbar, deleteToast} from './myToastr';
import {TabUtils} from './tabBroadcast'
import {refreshDashboard} from './refreshDashboard';

import {initDragParticipants} from './lobby_moderator_acceptDragger'

var callersoundplay = new Audio(callerSound);
callersoundplay.loop = true;

function initNotofication() {
    Push.Permission.request();
}

function masterNotify(data) {

    Push.Permission.request();
    if (data.type === 'notification') {
        notifymoderator(data)
    } else if (data.type === 'cleanNotification') {
        deleteToast(data.messageId);
    } else if (data.type === 'refresh') {
        refresh(data)
    } else if (data.type === 'modal') {
        loadModal(data)
    } else if (data.type === 'redirect') {
        redirect(data)
    } else if (data.type === 'snackbar') {
        setSnackbar(data.message, data.color)
    } else if (data.type === 'newJitsi') {
        //do nothing. Is handeled somewhere localy
    } else if (data.type === 'refreshDashboard') {
        refreshDashboard();
    } else if (data.type === 'endMeeting') {
        endMeeting(data)
    } else if (data.type === 'reload') {
        setTimeout(function () {
            location.reload();

        }, data.timeout)
    } else if (data.type === 'call') {
        callAddhock(data);
    } else {
        alert('Error, Please reload the page')
    }
}


function notifymoderator(data) {
    showPush(data);
    setSnackbar(data.message, data.color, false, data.messageId);
    $('.dragger').addClass('active');

    $('#sliderTop')
        .addClass('notification')
        .css('transform', 'translateY(0px)')
        .mouseover(function (e) {
            $('.dragger').removeClass('active');
            $('#sliderTop')
                .removeClass('notification')
            $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
        })
}


function refresh(data) {
    var reloadUrl = data.reloadUrl;

    $('#waitingUserWrapper').load(reloadUrl, function () {
        if (!$('#sliderTop').hasClass('notification')) {
            $('#sliderTop').css('transform', 'translateY(-' + $('#col-waitinglist').outerHeight() + 'px)');
        }
        initCircle();
        countParts();
        initDragParticipants();
    });
}

function endMeeting(data) {
    window.onbeforeunload = null;
    if (window.opener == null) {
        setTimeout(function () {
            window.location.href = data.url;
        }, data.timeout)
    } else {
        setTimeout(function () {
            window.close();
        }, data.timeout)
    }
}

function loadModal(data) {

    $('#loadContentModal').html(data.content).modal('show');
}


function redirect(data) {
    setTimeout(function () {
        window.location.href = data.url;
    }, data.timeout)

}

function countParts() {
    $('#lobbyCounter').text($('.waitingUserCard').length);
}

function showPush(data) {
    setTimeout(function () {
        TabUtils.lockFunction('notification' + data.messageId, function () {
            var audio = new Audio(notificationSound);
            audio.play();
            if (document.visibilityState === 'hidden') {
                Push.create(data.title, {
                    body: data.pushNotification,
                    icon: '/favicon.ico',
                    onClick: function (ele) {
                        window.focus();
                        this.close();
                    }
                });
            }
        }, 2500)
    }, Math.floor(Math.random() * 50) + 50);
}

function callAddhock(data) {
    setTimeout(function () {
        TabUtils.lockFunction('notification' + data.messageId, function () {
            callersoundplay.play()
            setTimeout(function () {
                callersoundplay.pause();
                callersoundplay.currentTime = 0;
            }, data.time)

            Push.create(data.title, {
                body: data.pushMessage,
                icon: '/favicon.ico',
                onClick: function (ele) {
                    window.focus();
                    this.close();
                }
            });

        }, 5000)
    }, Math.floor(Math.random() * 50) + 50);
    setSnackbar(data.message, data.color, true);
}

function stopCallerPlay() {
    callersoundplay.pause();
    callersoundplay.currentTime = 0;
}

export {masterNotify, initNotofication, stopCallerPlay}
