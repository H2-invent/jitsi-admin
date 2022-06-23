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

$(document).ready(function () {
    initGenerell();
    setTimeout(function () {
        $('.innerOnce').click(function (e) {
            $(this).addClass('d-none');
        })
    }, 500);
    initSchedulePublic()

});
$(window).on('load', function () {
    $('[data-toggle="popover"]').popover({html: true});
    $('[data-toggle="toastr"]').click(function (e) {
        console.log($(this))
        setSnackbar($(this).data('text'),$(this).data('type'))
    });
});
