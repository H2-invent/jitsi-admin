import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initCircle} from './initCircle'
import notificationSound from '../sound/notification.mp3'

function initNotofication() {
    Push.Permission.request();
}

function masterNotify(data) {
    Push.Permission.request();
    if (data.type === 'notification') {
        notifymoderator(data)
    } else if (data.type === 'refresh') {
        refresh(data)
    } else if (data.type === 'modal') {
        loadModal(data)
    } else if (data.type === 'redirect') {
        redirect(data)
    } else if (data.type === 'snackbar') {
        setSnackbar(data.message, data.color)
    } else if (data.type === 'newJitsi') {

    } else if (data.type === 'endMeeting') {
        window.location.href = data.forwardUrl;
    } else if (data.type === 'reload') {
        setTimeout(function () {
            location.reload();
        }, data.timeout)
    } else {
        alert('Error, Please reload the page')
    }
}

function setSnackbar(text, color) {
    $('#snackbar').text(text).removeClass('bg-danger').removeClass('bg-warning').removeClass('bg-success').removeClass('d-none').addClass('show bg-' + color).click(function (e) {
        $(this).removeClass('show');
    });
    $('#snackbar').mouseleave(function (e) {
        $(this).removeClass('show');
    })

}

function notifymoderator(data) {
    var audio = new Audio(notificationSound);
    audio.play();
    Push.create(data.title, {
        body: data.message,
        icon: '/favicon.ico',
        link: 'test',
        onClick: function (ele) {
            window.focus();
            this.close();
        }
    });
    setSnackbar(data.message, 'success');
    $('.dragger').addClass('active');

    $('#sliderTop')
        .addClass('notification')
        .css('top', '0px')
        .mouseover(function (e) {
            $('.dragger').removeClass('active');
            $('#sliderTop')
                .removeClass('notification')
                .css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
        })
}


function refresh(data) {
    var reloadUrl = data.reloadUrl;

    $('#waitingUserWrapper').load(reloadUrl, function () {
        if (!$('#sliderTop').hasClass('notification')) {
            $('#sliderTop').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
        }
        initCircle();
    });
}


function loadModal(data) {
    $('#loadContentModal').html(data.content).modal('show');
}


function redirect(data) {
    setTimeout(function () {
        window.location.href = data.url;
    }, data.timeout)

}

export {masterNotify, initNotofication}