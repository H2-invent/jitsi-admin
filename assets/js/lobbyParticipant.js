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

initWebcam()
const es = new EventSource(topic);
es.onmessage = e => {
    var data = JSON.parse(e.data)
    masterNotify(data)
    if (data.type === 'newJitsi') {
        initJitsiMeet(data);
    }
}
const broadcast = new EventSource(topicBroadcast);
broadcast.onmessage = e => {
    var data = JSON.parse(e.data);

}

initCircle();
var counter = 0;
var interval;
var text;
$('.renew').click(function (e) {
    e.preventDefault();
    if (counter === 0) {
        text = $(this).text();
        $.get($(this).attr('href'), function (data) {
            counter = 60;
            interval = setInterval(function () {
                counter = counter - 1;
                $('.renew').text(text + ' (' + counter + ')');
                if (counter === 0) {
                    $('.renew').text(text);
                    clearInterval(interval);
                }
            }, 1000);
            $('#snackbar').text(data.message).removeClass('d-none').addClass('show bg-' + data.color).click(function (e) {
                $('#snackbar').removeClass('show');
            })
        })
    }
})
$('.leave').click(function (e) {
    e.preventDefault();

    text = $(this).text();
    $.get($(this).attr('href'), function (data) {
        window.location.href = "/";
    })

})

function initJitsiMeet(data) {
    var options =data.options.options;
    console.log(options);
    options.device = choosenId;
    options.parentNode = document.querySelector( data.options.parentNode);
    console.log(options);
    const api = new JitsiMeetExternalAPI(data.options.domain, options);
    $(data.options.parentNode).prependTo('body').css('height', '100vh').find('iframe').css('height', '100vh');
    $('#content').remove();
    $('.imageBackground').remove();
    document.title = data.options.roomName
}

console.log('handler');
// window.addEventListener("beforeunload", function(event) {
//     $.get(removeUrl)
// });
