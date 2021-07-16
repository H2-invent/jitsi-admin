/*
 * Welcome to your app's main JavaScript file!
 *
 */


import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import {initSchedulePublic} from './scheduling'
$(document).ready(function () {
    setTimeout(function () {
        $('#snackbar').addClass('show').click(function (e) {
            $('#snackbar').removeClass('show');
        })
    }, 500);
    initSchedulePublic()
});
$(window).on('load', function () {
    $('[data-toggle="popover"]').popover({html: true});
});
