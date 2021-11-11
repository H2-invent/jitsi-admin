/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import stc from'string-to-color/index';
import {masterNotify} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam} from './cameraUtils'
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

es.onmessage = e => {
    var data = JSON.parse(e.data)
   masterNotify(data)
}
initCircle();


