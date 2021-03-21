/*
 * Welcome to your app's main JavaScript file!
 *
 */
import(/* webpackChunkName: "H2" */ '../css/app.scss');

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