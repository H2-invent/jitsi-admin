/*
 * Welcome to your app's main JavaScript file!
 *
 */
import '../css/app.scss';
//import(/* webpackChunkName: "H2" */ '../css/app.scss');
import $ from 'jquery';

global.$ = global.jQuery = $;
import ('popper.js');

import('bootstrap');
import('mdbootstrap');
import ('jquery-confirm');
import * as h2Button from 'h2-invent-apps';
import flatpickr from 'flatpickr';
import { Spanish } from 'flatpickr/dist/l10n/es.js'; //Added spanish translation
import {Calendar} from '@fullcalendar/core';
import dayGridPlugin from '@fullcalendar/daygrid';
import bootstrapPlugin from '@fullcalendar/bootstrap';
import momentPlugin from '@fullcalendar/moment';
import listPlugin from '@fullcalendar/list';
import Chart from 'chart.js';
import autosize from 'autosize'
import ClipboardJS from 'clipboard'
import {initScheduling} from './scheduling'
import * as Toastr from 'toastr'
import {initGenerell} from './init'

import {initKeycloakGroups} from './keyCloakGroupsInit';

$.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results == null) {
        return null;
    }
    return decodeURI(results[1]) || 0;
}
addEventListener('load', function () {
    var url = (new URLSearchParams(window.location.search)).get('modalUrl');
    if (url !== null) {
        $('#loadContentModal').load(atob(url), function (data,status) {
            if ( status === "error" ) {
                window.location.reload();
            }else {
                $('#loadContentModal ').modal('show');
            }

        });
    }
});

$(document).ready(function () {
    $('.switchDarkmode').change(function (e) {
        var val = 0;
        if ($(this).prop('checked')) {
            val = 1
        }
        setCookie('DARK_MODE', val, 365);
        window.location.reload();
    })
    setTimeout(function () {
        $('#snackbar').addClass('show').click(function (e) {
            $('#snackbar').removeClass('show');
        })
    }, 500);

    if (importBBB) {
        h2Button.init(bbbUrl);
    }
    if (notificationUrl !== "") {
        h2Button.initNotification(notificationUrl);
    }
    initGenerell();
    initDropDown();

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

    $('.flatpickr').flatpickr({
	locale: Spanish, //Use spanish
        minDate: "today",
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });
    initCopytoClipboard();

});
$(window).on('load', function () {
    $('[data-toggle="popover"]').popover({html: true});

});

$(document).on('click', '.confirmHref', function (e) {
    e.preventDefault();
    var url = $(this).prop('href');
    var text = $(this).data('text');
    if (typeof text === 'undefined') {
	text = '¿Desea continuar?' //Translation

    }

    $.confirm({
        title: 'Confirmacion',
        content: text,
        theme: 'material',
        buttons: {
            confirm: {
                text: 'OK', // text for button
                btnClass: 'btn-outline-danger btn', // class for the button
                action: function () {
                    window.location.href = url;
                },


            },
            cancel: {
		            text: 'Cancelar', //Tranlation
                btnClass: 'btn-outline-primary btn', // class for the button
            },
        }
    });

})

$(document).on('click', '.loadContent', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    $('#loadContentModal').load(url, function (data,status) {
        if ( status === "error" ) {
            window.location.reload();
        }else {
            $('#loadContentModal ').modal('show');
        }

    });
});

function initServerFeatures() {

    getMoreFeature($('.moreFeatures').val())
    $('.moreFeatures').change(function () {
        getMoreFeature($(this).val());
    })
}

$('#loadContentModal').on('shown.bs.modal', function (e) {
    initScheduling();
    $('[data-toggle="popover"]').popover({html: true});
    $('.flatpickr').flatpickr({
  	locale : Spanish, //Use Spanish
        minDate: "today",
        enableTime: true,
        time_24hr: true,
        defaultMinute: 0,
        minuteIncrement: 15,
        altFormat: 'Y-m-d H:i',
        dateFormat: 'Y-m-d H:i',
        altInput: true
    });
    $( 'form' ).submit(function( event ) {
        var btn = $(this).find('button[type=submit]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> ' + btn.text());
        btn.prop("disabled",true)
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

    if (typeof $('room_public') !== 'undefined') {
        if ($('#room_public').prop('checked')) {
            $('#maxParticipants').collapse('show')
        } else {
            $('#maxParticipants').collapse('hide')
        }
        $('#room_public').change(function () {
            if ($('#room_public').prop('checked')) {
                $('#maxParticipants').collapse('show')
            } else {
                $('#maxParticipants').collapse('hide')
            }
        })
    }
    initCopytoClipboard();

    initSearchUser();
    initServerFeatures();
    initRepeater();

    initKeycloakGroups();

    if(getCookie('room_server')){
        $('#room_server').val(getCookie('room_server'))
    }
    $('#room_server').change(function (){

        setCookie('room_server',$(this).val(),1000);
    })
    var ctx = document.getElementById("lineChart").getContext('2d');
    var myChart = new Chart(ctx, {
        type: 'line',
        data: data,
        options: options
    });
});
$(document).on('click', '.directSend', function (e) {
    var $url = $(this).prop('href');
    var $targetUrl = $(this).data('url');
    var target = $(this).data('target');

    e.preventDefault();
    $.get($url, function () {
        $(target).closest('div').load($targetUrl + ' ' + target);
    })
});
$(".clickable-row").click(function () {
    window.location = $(this).data("href");
});
$('#ex1-tab-3-tab').on('shown.bs.tab', function (e) {
    renderCalendar();
})

function renderCalendar() {

    var calendarEl = document.getElementById('calendar');
    var calendar = new Calendar(calendarEl, {
        plugins: [dayGridPlugin, bootstrapPlugin, momentPlugin, listPlugin],
        themeSystem: 'bootstrap',
        events: '/api/v1/getAllEntries',
        lang: 'es',
        timeFormat: 'H(:mm)',
        displayEventEnd: true,

    });
    calendar.render();

}

function initSearchUser() {
    autosize($('#new_member_member'));
    autosize($('#new_member_moderator'));
    $('#searchUser').keyup(function (e) {
        var $ele = $(this);
        var $search = $ele.val();
        var $url = $ele.attr('href') + '?search=' + $search;
        if ($search.length > 0) {
            $.getJSON($url, function (data) {
                var $target = $('#participantUser');
                $target.empty();
                for (var i = 0; i < data.length; i++) {
                    $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + data[i] + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i> ' + data[i] + '</a>');
                }
                $('[data-toggle="tooltip"]').tooltip();
                $('.chooseParticipant').click(function (e) {
                    e.preventDefault();
                    var $textarea = $('#new_member_member');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).data('val') + "\n" + data);
                    $('#searchUser').val('');
                    autosize.update($textarea);
                })
                $('.chooseModerator').click(function (e){
                    e.stopPropagation();
                    $('#moderatorCollapse').collapse('show');
                    var $textarea = $('#new_member_moderator');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).closest('.chooseParticipant').data('val') + "\n" + data);
                    $('#searchUser').val('');
                    autosize.update($textarea);
                })
            })
        }

    })
}

function initDropDown() {
    $('.dropdownTabToggle').click(function (e) {
        e.preventDefault();
        var $ele = $(this);
        var $target = $ele.attr('href');
        $($target).tab('show');
        $ele.closest('.tabDropdown').find('button').text($ele.text());
        $ele.closest('.dropdown-menu').find('.active').removeClass('active');
        $ele.addClass('active');
    })


}

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

function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}
function initCopytoClipboard(){

    var clipboard = new ClipboardJS('.copyLink');
}
function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for(var i = 0; i <ca.length; i++) {
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
function initRepeater(){
    $('.repeater').addClass('d-none');
    $('#repeater_'+$('#repeater_repeatType').val()).removeClass('d-none');

    $('#repeater_repeatType').change(function (){

        $('.repeater').addClass('d-none');
        $('#repeater_'+$(this).val()).removeClass('d-none');
    })
}
