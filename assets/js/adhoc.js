/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';
import {createIframe} from "./createConference";

global.$ = global.jQuery = $;
import ('jquery-confirm');
let title = "Bestätigung";
let cancel = "Abbrechen";
let ok = "OK";


function initconfirmLoadOpenPopUp() {

    $(document).on('click', '.adhocConfirm', function (e) {

        e.preventDefault();
        var url = $(this).prop('href');
        var text = $(this).data('text');
        if (typeof text === 'undefined') {

            text = 'Wollen Sie die Aktion durchführen?'
        }
        $.confirm({
            title: title,
            content: 'url:'+url,
            theme: 'material',
            columnClass: 'col-md-8 col-12 col-lg-6',
            buttons: {
                confirm: {
                    text: ok, // text for button
                    btnClass: 'btn-outline-danger btn', // class for the button
                    action: function () {
                        var url = $('#adhocTag').find(":selected").data('value');


                        $.get(url, function (data) {
                            if(data.popups){
                                for (var value of data.popups){
                                    createIframe(value.url,value.title);
                                }
                            }
                        })
                    },
                },
                cancel: {
                    text: cancel, // text for button
                    btnClass: 'btn-outline-primary btn', // class for the button
                },
            }
        });
    })
}


function initAdhocMeeting(titleL, cancelL, okL) {
    title = titleL;
    cancel = cancelL;
    ok = okL;

    initconfirmLoadOpenPopUp();

}
function hideTooltip() {
    $('.tooltip').remove();
}

export {initAdhocMeeting}