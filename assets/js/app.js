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
import ClipboardJS from 'clipboard';
import {initScheduling} from './scheduling';
import * as Toastr from 'toastr';
import {initGenerell} from './init';
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
    if (typeof(modalUrl) !== 'undefined'){
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
    $('[data-toggle="popover"]').popover({html: true});
    $('[data-toggle="tooltip"]').tooltip();
});

$(document).on('click', '.stopCloseDropdown', function (e) {
    console.log('1.2sdf');
    e.stopPropagation();
});

$(document).on('click', '.loadContent', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    $('#loadContentModal').load(url, function (data, status) {
        if (status === "error") {
            window.location.reload();
        } else {
            if (!$('#loadContentModal ').hasClass('show')) {
                $('#loadContentModal').modal('show');
            }else {
                initNewModal();
            }
        }
    });
});

function initServerFeatures() {
    getMoreFeature($('.moreFeatures').val())
    $('.moreFeatures').change(function () {
        getMoreFeature($(this).val());
    })
}

$('#modalAdressbook').on('shown.bs.modal', function (e) {
    initalSetUnderline('#modalAdressbook .underline');
});

$('#loadContentModal').on('shown.bs.modal', function (e) {
  initNewModal(e)
});

function initNewModal(e){

    initScheduling();

    $('[data-mdb-toggle="popover"]').popover({html: true});

    $('[data-mdb-toggle="tooltip"]').tooltip()

    initdateTimePicker('.flatpickr');
    initNewRoomModal();
    $('form').submit(function (event) {
        var btn = $(this).find('button[type=submit]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> ' + btn.text());
        btn.prop("disabled", true)
    });


    $('.generateApiKey').click(function (e) {
        e.preventDefault();
        $('#enterprise_apiKey').val(Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15));
    })
    $('#jwtServer').change(function () {
        if ($('#jwtServer').prop('checked')) {
            $('#appId').collapse('show')
        } else {
            $('#appId').collapse('hide')
        }
    });

    initCopytoClipboard();
    initSearchUser();
    initServerFeatures();
    initRepeater();
    initKeycloakGroups();
    initAddressGroupSearch();
    initChart();
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
    if (document.getElementById("lineChart") !== null) {
        var ctx = document.getElementById("lineChart").getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: options
        });
    }
}

$(".clickable-row").click(function () {
    window.location = $(this).data("href");
});
$('#ex1-tab-3-tab').on('shown.bs.tab', function (e) {

})


function getMoreFeature(id) {
    if (typeof id !== 'undefined') {
        $.getJSON(moreFeatureUrl, 'id=' + id, function (data) {
            var feature = data.feature;
            for (var prop in feature) {
                if (feature[prop] == true) {
                    $('#' + prop).removeClass('d-none')
                } else {
                    $('#' + prop).addClass('d-none')
                }
            }
        })
    }
}



function initCopytoClipboard() {

    var clipboard = new ClipboardJS('.copyLink');
}

$(document).on('click', '.testVideo', function (e) {
    e.preventDefault();
    var $url = $(this).attr('href');
    $url += '?url=' + $('#server_url').val();
    $url += '&cors=' + $('#server_corsHeader').prop('checked');
    console.log($url);
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

function initRepeater() {
    $('.repeater').addClass('d-none');
    $('#repeater_' + $('#repeater_repeatType').val()).removeClass('d-none');

    $('#repeater_repeatType').change(function () {

        $('.repeater').addClass('d-none');
        $('#repeater_' + $(this).val()).removeClass('d-none');
    })
}

$('.sidebarToggle').click(function () {
    $('#sidebar').toggleClass('showSidebar');
    $('.sidebarToggle').toggleClass('d-none');

})
