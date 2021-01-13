/*
 * Welcome to your app's main JavaScript file!
 *
 */
import '../css/app.css';
import 'jquery'
global.$ = global.jQuery = $;
import 'popper.js';

import('bootstrap');
import('mdbootstrap');
import ('jquery-confirm');
import * as h2Button from 'h2-invent-apps';
import flatpickr from 'flatpickr'



$.urlParam = function (name) {
    var results = new RegExp('[\?&]' + name + '=([^&#]*)').exec(window.location.href);
    if (results == null) {
        return null;
    }
    return decodeURI(results[1]) || 0;
}
addEventListener('load',function() {
    var url = (new URLSearchParams(window.location.search)).get('modalUrl');
    if (url !== null) {
        $('#loadContentModal').load(atob(url), function () {
            $('#loadContentModal ').modal('show');
        });
    }
});

$(document).ready(function () {
    setTimeout(function () {
        $('#snackbar').addClass('show');
        setTimeout(function () {
            $('#snackbar').removeClass('show');
        }, 3000);
    }, 500);
    h2Button.init();


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
        minDate: "today",
        enableTime: true,
        dateFormat: "Y-m-d H:i",
    });

});

$(document).on('click', '.confirmHref', function (e) {
    e.preventDefault();
    var url = $(this).prop('href');
    $.confirm({
        title: 'Bestätigung',
        content: 'Wollen Sie die Aktion durchführen?',
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
                text: 'Abbrechen', // text for button
                btnClass: 'btn-outline-primary btn', // class for the button
            },
        }
    });

})

$(document).on('click', '.loadContent', function (e) {
    e.preventDefault();
    var url = $(this).attr('href');
    $('#loadContentModal').load(url, function () {
        $('#loadContentModal ').modal('show');

    });
});
$('#loadContentModal').on('shown.bs.modal', function (e) {
    $('.flatpickr').flatpickr({
        minDate: "today",
        enableTime: true,
        time_24hr: true,
        defaultMinute: 0,
        minuteIncrement: 15,
        altFormat: 'd.m.Y H:i',
        dateFormat: 'Y-m-d H:i',
        altInput: true
    });

    $('#jwtServer').change(function () {
        if ($('#jwtServer').prop('checked')) {
            $('#appId').collapse('show')
        } else {
            $('#appId').collapse('hide')
        }
    });
});

$(".clickable-row").click(function () {
    window.location = $(this).data("href");
});

