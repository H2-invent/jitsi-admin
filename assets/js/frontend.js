/*
 * Welcome to your app's main JavaScript file!
 *
 */
import '../css/app.css';

import $ from 'jquery';
import 'summernote/dist/summernote-bs4';

global.$ = global.jQuery = $;

$(document).ready(function () {
    setTimeout(function () {
        $('#snackbar').addClass('show');
        setTimeout(function () {
            $('#snackbar').removeClass('show');
        }, 3000);
    }, 500);

    $('.summernote').summernote({
        placeholder: 'Geben Sie hier weitere Informationen zu Ihrer Anfrage ein.',
        tabsize: 2,
        height: 400,
        focus: true,
        lang: 'de-DE',
        toolbar: [
            ['style', ['pre']],
            ['font', ['bold', 'underline', 'clear']],
            ['color', []],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link']],
            ['view', ['fullscreen']]
        ],
    });
});