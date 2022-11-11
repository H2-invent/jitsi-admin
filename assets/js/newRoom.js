import $ from "jquery";
import ('jquery-confirm');
import {setCookie} from './cookie'
import autosize from "autosize";

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initNewRoomModal() {
    $('#saveRoom').click(function (e) {
        e.preventDefault();
        var btn = $(this);
        btn.html('<i class="fas fa-spinner fa-spin"></i> ' + btn.text());
        btn.prop("disabled", true)
        let form = $('#newRoom_form');
        let url = form.attr('action');
        let blocktext = form.data('blocktext');
        let kategorieInput = form.find('')
        if (typeof (blocktext) !== 'undefined') {
            $.confirm({
                title: title,
                content: blocktext,
                theme: 'material',
                buttons: {
                    confirm: {
                        text: ok, // text for button
                        btnClass: 'btn-outline-danger btn', // class for the button
                        action: function () {
                            sendToServer(form, url)
                        },


                    },
                    cancel: {
                        text: cancel, // text for button
                        btnClass: 'btn-outline-primary btn', // class for the button
                        action:function () {
                            removeDisableBtn(form);
                        }
                    },
                }
            });

        } else {
            console.log('kdsjhkfhdskj')
            sendToServer(form, url)
        }
    })

    autosize($('#room_agenda'));
    $("#newRoom_form").submit(function (e) {

        e.preventDefault(); // avoid to execute the actual submit of the form.
    });


    initMoreSettings();

}

function sendToServer(form, url) {
    $.ajax({
        type: "POST",
        url: url,
        data: form.serialize(), // serializes the form's elements.
        success: function (data) {
            var $res = data;
            if ($res['error'] === false) {
                if (typeof $res['cookie'] !== 'undefined') {
                    for (const [key, value] of Object.entries($res['cookie'])) {

                        setCookie(key, value, 1000);
                    }
                }
                window.location.href = $res['redirectUrl'];
            } else {
                $('.formError').remove();
                for (var i = 0; i < $res['messages'].length; i++) {
                    $('<div class="alert alert-dismissible fade show alert-danger w-100 formError" role="alert">' + $res['messages'][i] + '  <button type="button" class="btn-close" data-mdb-dismiss="alert" aria-label="Close"></button>' +
                        '</div>')
                        .insertBefore($('#saveRoom'))
                }
                removeDisableBtn(form);
            }
        }
    });
}

function removeDisableBtn(form) {
    var btn = $('#saveRoom');
    btn.find('.fas').remove();
    btn.prop("disabled", false)
}
function initMoreSettings() {
    if (typeof $('#room_persistantRoom') !== 'undefined') {
        if ($('#room_persistantRoom').prop('checked')) {
            $('#roomStartForm').collapse('hide')
            if ($('#room_totalOpenRooms').prop('checked')) {
                $('#totalOpenRoomsOpenTime').collapse('show');
            } else {
                $('#totalOpenRoomsOpenTime').collapse('hide');
            }
        } else {
            $('#roomStartForm').collapse('show')
            $('#totalOpenRoomsOpenTime').collapse('hide');
        }
        $('#room_persistantRoom').change(function () {
            if ($('#room_persistantRoom').prop('checked')) {
                $('#roomStartForm').collapse('hide')
                if ($('#room_totalOpenRooms').prop('checked')) {
                    $('#totalOpenRoomsOpenTime').collapse('show');
                } else {
                    $('#totalOpenRoomsOpenTime').collapse('hide');
                }
            } else {
                $('#roomStartForm').collapse('show')
                $('#totalOpenRoomsOpenTime').collapse('hide');
            }
        })
    }

    if (typeof $('#room_public') !== 'undefined') {
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
}

export {initNewRoomModal}