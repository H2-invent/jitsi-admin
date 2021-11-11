import $ from 'jquery';
import stc from "string-to-color";

global.$ = global.jQuery = $;

function initCircle(){
    $('.initialCircle').each(function (i) {
        $(this).css('background-color',stc($(this).text()));
    })
    $('.senddirect').click(function (e) {
        e.preventDefault();
        $.get($(this).attr('href'), function (data) {
            $('#snackbar').text(data.message).removeClass('d-none').addClass('show bg-' + data.color).click(function (e) {
                $('#snackbar').removeClass('show');
            })
        })
    })
}
export {initCircle}