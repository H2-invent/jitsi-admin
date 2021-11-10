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
import {notifymoderator} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam} from './cameraUtils'
initWebcam()
const es = new EventSource(topic);
es.onmessage = e => {
    var data = JSON.parse(e.data)
    notifymoderator(data)
}
initCircle();
$('.renew').click(function (e){
    e.preventDefault();
    $.get($(this).attr('href'),function (data) {
        console.log(data);
    })
})

