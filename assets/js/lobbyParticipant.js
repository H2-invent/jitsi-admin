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
import {initWebcam} from './cameraUtils'
import * as url from "url";
initWebcam()
const es = new EventSource(topic);
es.onmessage = e => {
    var data = JSON.parse(e.data)
    masterNotify(data)
}
initCircle();
var counter = 0;
var interval;
var text;
$('.renew').click(function (e) {
    e.preventDefault();
    if(counter === 0){
        text = $(this).text();
        $.get($(this).attr('href'), function (data) {
            counter = 60;
            interval = setInterval(function () {
                counter = counter-1;
                $('.renew').text(text+' ('+counter+')');
                if (counter === 0){
                    $('.renew').text(text);
                    clearInterval(interval);
                }
            },1000);
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
console.log('handler');
window.addEventListener("beforeunload", function(event) {
    $.get(removeUrl)
});
