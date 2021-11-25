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

initWebcam()
const es = new EventSource(topic);

$('.directSend').click(function (e) {
    e.preventDefault();
    $.get($(this).attr('href'), function (data) {
        $('#snackbar').text(data.message).removeClass('d-none').addClass('show bg-' + data.color).click(function (e) {
            $('#snackbar').removeClass('show');
        })
    })
})

$('.startIframe').click(function (e) {
    $(this).remove();
    $('#colWebcam').remove();
    $('#col-waitinglist').removeClass('col-lg-9 col-md-6').addClass('col-12');
    e.preventDefault();
    moveWrapper();
    options.device = choosenId;
    const api = new JitsiMeetExternalAPI(domain, options);
    $('#jitsiWrapper').find('iframe').css('height', '100vh');
})
es.onmessage = e => {
    var data = JSON.parse(e.data)
    masterNotify(data)
}

function moveWrapper() {
    $('#jitsiWrapper').prependTo('body').css('height', '100vh').find('.lobbyWindow').css('height', 'inherit').find('#jitsiWindow').css('height', 'inherit');
    $('#jitsiWindow').css('height', '100vh');
    $('.container-fluid').remove();
    $('.lobbyWindow').wrap('<div class="container-fluid waitinglist" id="sliderTop">').append('<div class="dragger">Hier Ziehen</div>');
    $('#col-waitinglist').addClass('large').closest('.container-fluid').css('top', '-' + $('#col-waitinglist').outerHeight() + 'px');
}

initCircle();


