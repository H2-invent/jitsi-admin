/*
 * Welcome to your app's main JavaScript file!
 *
 */
import $ from 'jquery';

global.$ = global.jQuery = $;

import * as mdb from 'mdb-ui-kit'; // lib

import {initSchedulePublic} from './scheduling'
import {initGenerell} from './init';
import {setSnackbar} from "./myToastr";
import * as h2Button from "h2-invent-apps";

$(document).ready(function () {
    initGenerell();
    setTimeout(function () {
        $('.innerOnce').click(function (e) {
            $(this).addClass('d-none');
        })
    }, 500);
    initSchedulePublic();
    if (notificationUrl !== "") {
        h2Button.initNotification(notificationUrl);
    }

});
$(window).on('load', function () {
    $('[data-mdb-toggle="popover"]').popover({html: true});
    $('[data-mdb-toggle="toastr"]').click(function (e) {

        setSnackbar($(this).data('text'),$(this).data('type'))
    });
});
