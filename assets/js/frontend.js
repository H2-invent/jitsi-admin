/*
 * Welcome to your app's main JavaScript file!
 *
 */
import $ from 'jquery';
import { Popover, initMDB } from "mdb-ui-kit";
global.$ = global.jQuery = $;



import {initSchedulePublic} from './scheduling'
import {initGenerell} from './init';
import {setSnackbar} from "./myToastr";
import * as h2Button from "h2-invent-apps";
import {initAllComponents} from "./confirmation";

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


    initMDB({ Popover });
    initAllComponents();
    $('[data-mdb-toggle="toastr"]').click(function (e) {

        setSnackbar($(this).data('text'),'',$(this).data('type'))
    });
});
