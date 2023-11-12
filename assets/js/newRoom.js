import $ from "jquery";

import ('jquery-confirm');
import {setCookie} from './cookie'
import autosize from "autosize";
import {wrapOneSelect} from "./init";

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
                columnClass: 'col-md-8 col-12 col-lg-6',
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
                        action: function () {
                            removeDisableBtn(form);
                        }
                    },
                }
            });

        } else {
            sendToServer(form, url)
        }
    })

    autosize($('textarea'));
    $("#newRoom_form").submit(function (e) {

        e.preventDefault(); // avoid to execute the actual submit of the form.
    });


    initMoreSettings();
    initTags();

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
    let persistantRoom =  $('#room_persistantRoom');
    if (typeof persistantRoom!== 'undefined') {
        if (persistantRoom.prop('checked')) {
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
        persistantRoom.change(function () {
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
    let publicRoom =  $('.public_checkbox');
    if (typeof publicRoom !== 'undefined') {
        if (publicRoom.prop('checked')) {
            $('#maxParticipants').collapse('show')
        } else {
            $('#maxParticipants').collapse('hide')
        }
        publicRoom.change(function () {
            if (publicRoom.prop('checked')) {
                $('#maxParticipants').collapse('show')
            } else {
                $('#maxParticipants').collapse('hide')
            }
        })
    }
}

function initTags() {
    const newRoomMOdal = document.getElementById('newRoomMOdal');
    if (!newRoomMOdal){
        return;
    }
    const form = newRoomMOdal.querySelector('form');
    const form_select_Server = document.querySelector('.fakeserver');
    const form_select_tags = document.getElementById('form_tag_wrapper');
    const form_token =  document.querySelectorAll('[id~="__token"]');
    const updateForm = async (data, url, method) => {

        if (isParameterInUrl(url)) {
            url += '&';
        } else {
            url += '?';
        }

        const req = await fetch(url + new URLSearchParams(
            {
                serverfake: data
            }
        ),
            {
                method: 'GET',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                    'charset': 'utf-8'
                }
            });

        const text = await req.text();

        return text;
    };

    const parseTextToHtml = (text) => {
        const parser = new DOMParser();
        const html = parser.parseFromString(text, 'text/html');

        return html;
    };

    const changeOptions = async (e) => {
        const requestBody = e.target.value;
        const updateFormResponse = await updateForm(requestBody, form.getAttribute('action'), form.getAttribute('method'));
        const html = parseTextToHtml(updateFormResponse);

        const new_form_select_position = html.getElementById('form_tag_wrapper');
        form_select_tags.innerHTML = new_form_select_position.innerHTML;
        wrapOneSelect(form_select_tags.querySelector('select'));
        form_token.value = html.querySelectorAll('[id~="__token"]').value;
    };
    if (form_select_Server) {
        form_select_Server.addEventListener('change', (e) => changeOptions(e));
    }

}

function isParameterInUrl(url) {
    return url.includes('?');
}

export {initNewRoomModal}