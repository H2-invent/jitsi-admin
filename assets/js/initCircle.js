import $ from 'jquery';
import stc from "string-to-color";
import {setSnackbar} from './myToastr'
global.$ = global.jQuery = $;

function initCircle(){
    $('.initialCircle').each(function (i) {
        $(this).css('background-color',stc($(this).text()));
    })
    $('.senddirect').click(function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            setSnackbar(data.message,data.color);

        })
    })
}
export {initCircle}