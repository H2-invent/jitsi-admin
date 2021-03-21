/*
 * Welcome to your app's main JavaScript file!
 *
 */


import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');

$(document).ready(function () {
    setTimeout(function () {
        $('#snackbar').addClass('show');
        setTimeout(function () {
            $('#snackbar').removeClass('show');
        }, 3000);
    }, 500);
});
$(window).on('load', function () {
    $('[data-toggle="popover"]').popover({html: true});
});
