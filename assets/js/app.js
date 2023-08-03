/*
 * Welcome to your app's main JavaScript file!
 *
 */
import '../css/app.scss';

//import(/* webpackChunkName: "H2" */ '../css/app.scss');
import $ from 'jquery';


global.$ = global.jQuery = $;

import * as mdb from 'mdb-ui-kit'; // lib

import ('jquery-confirm');
import * as h2Button from 'h2-invent-apps';
import flatpickr from 'flatpickr';
import autosize from 'autosize';

import {initScheduling} from './scheduling';
import * as Toastr from 'toastr';
import {initCopytoClipboard, initGenerell, initNewModal} from './init';
import {initKeycloakGroups} from './keyCloakGroupsInit';
import {initAddressGroupSearch, initListSearch} from './addressGroup';
import {initSearchUser} from './searchUser';
import {initRefreshDashboard} from './refreshDashboard';
import {initdateTimePicker} from '@holema/h2datetimepicker';
import {initAjaxSend} from './confirmation'
import {attach, init} from 'node-waves'
import {initNewRoomModal} from './newRoom'
import {initTabs, initalSetUnderline} from 'h2-invent-material-tabs'
import {initDashboardnotification} from './dashBoardNotification'
import {initChart} from './chart'
import {Chart} from 'chart.js'


addEventListener('load', function () {
    var param = (new URLSearchParams(window.location.search)).get('modalUrl');
    let url = '';
    if (param !== null) {
        url = atob(param);
    }
    if (typeof (modalUrl) !== 'undefined') {
        url = atob(modalUrl);
    }
    if (url !== null) {
        if (url.startsWith('/')) {
            $('#loadContentModal').load(url, function (data, status) {
                if (status === "error") {
                    window.location.reload();
                } else {
                    $('#loadContentModal ').modal('show');
                }

            });
        }
        let search = new URLSearchParams(window.location.search);
        search.delete('modalUrl');
        let location = window.location.pathname;
        if (search.toString().length > 0) {
            location += '?' + search.toString();
        }

        window.history.pushState({}, document.title, location);
    }
});

$(document).ready(function () {

    initTabs('.nav-mat-tabs');
    attach('.btn', ['waves-effect']);
    attach('.nav-item', ['waves-effect']);
    init();


    initDashboardnotification(topic);

    setTimeout(function () {
        $('.innerOnce').click(function (e) {
            $(this).addClass('d-none');
        })
    }, 500);

    if (importBBB) {
        h2Button.init(bbbUrl);
    }
    if (notificationUrl !== "") {
        h2Button.initNotification(notificationUrl);
    }
    initGenerell();

    initRefreshDashboard(refreshDashboardTime, refreshDashboardUrl)
    initListSearch();
    initAjaxSend(confirmTitle, confirmCancel, confirmOk);

    $('#dismiss, .overlay').on('click', function () {
        // hide sidebar
        $('#sidebar').removeClass('active');
        // hide overlay
        $('.overlay').removeClass('active');
    });

    $('#sidebarCollapse').on('click', function () {
        // open sidebar
        $('#sidebar').addClass('active');
        // fade in the overlay
        $('.overlay').addClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });


    initCopytoClipboard();
    let url = new URLSearchParams(window.location.search);
    url.delete('snack');
    let location = window.location.pathname;
    if (url.toString().length > 0) {
        location += '?' + url.toString();
    }
    window.history.pushState({}, document.title, location);
});
$(window).on('load', function () {
    $('[data-mdb-toggle="popover"]').popover({html: true});
    $('[data-mdb-toggle="tooltip"]').tooltip('hide');
    $('.tooltip').remove();
    $('[data-mdb-toggle="tooltip"]').tooltip();
});

$(document).on('click', '.stopCloseDropdown', function (e) {
    e.stopPropagation();
});



$('#modalAdressbook').on('shown.bs.modal', function (e) {
    initalSetUnderline('#modalAdressbook .underline');
});




$(".clickable-row").click(function () {
    window.location = $(this).data("href");
});
$('#ex1-tab-3-tab').on('shown.bs.tab', function (e) {

})




$(document).on('click', '.testVideo', function (e) {
    e.preventDefault();
    var $url = $(this).attr('href');
    $url += '?url=' + $('#server_url').val();
    $url += '&cors=' + $('#server_corsHeader').prop('checked');
    window.open($url, '_blank').focus();
})


function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}



$('.sidebarToggle').click(function () {
    $('#sidebar').toggleClass('showSidebar');
    $('.sidebarToggle').toggleClass('d-none');

})
