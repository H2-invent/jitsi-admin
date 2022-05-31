/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

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
        console.log('1.234');
        $.confirm({
            title: title,
            content: 'url:'+url,
            theme: 'material',
            buttons: {
                confirm: {
                    text: ok, // text for button
                    btnClass: 'btn-outline-danger btn', // class for the button
                    action: function () {
                        var url = $('#adhocTag').find(":selected").data('value');
                        console.log(url);
                        const win = window.open('about:blank');
                        $.get(url, function (data) {
                            if(data.popups){
                                data.popups.forEach(function (value,i) {
                                    win.location.href = value;
                                })
                            }
                            window.location.href = data.redirectUrl;
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