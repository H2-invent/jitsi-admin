/*
 * Welcome to your app's main JavaScript file!
 *
 */
import '../css/join.scss';

import $ from 'jquery';

global.$ = global.jQuery = $;
import * as mdb from 'mdb-ui-kit'; // lib

import {initSchedulePublic} from './scheduling'

import {initGenerell} from './init';


$(document).ready(function () {
    initGenerell()
    var domain =
        setTimeout(function () {
            $('#snackbar').addClass('show');
            setTimeout(function () {
                $('#snackbar').removeClass('show');
            }, 3000);
        }, 500);
    checkRoom();
    initSchedulePublic();

});
$(window).on('load', function () {
    $('[data-mdb-toggle="popover"]').popover({html: true});
});

function checkRoom() {
    $.getJSON(checkUrl, function (data) {
        if (!data.error) {
            window.location.replace(data.url);
            clearInterval(intervalID)
        }
    })
}



function getTimeRemaining() {
    const total = Date.parse(startDate) - Date.parse(new Date());
    const seconds = (Math.floor((total / 1000) % 60)).toString();
    const minutes = (Math.floor((total / 1000 / 60) % 60)).toString();
    const hours = (Math.floor((total / (1000 * 60 * 60)) % 24)).toString();
    const days = (Math.floor(total / (1000 * 60 * 60 * 24))).toString();
   $('#countdown').text(
       ('00'+hours).substring(hours.length)+':'
       +('00'+minutes).substring(minutes.length)+':'
       +('00'+seconds).substring(seconds.length)
   );
}

var intervalID = setInterval(checkRoom, 5000);
if(typeof startDate !=='undefined'){
   setInterval(getTimeRemaining, 1000);
}
