import $ from 'jquery';
import stc from "string-to-color";

global.$ = global.jQuery = $;

function initCircle(){
    $('.initialCircle').each(function (i) {
        $(this).css('background-color',stc($(this).text()));
    })
}
export {initCircle}