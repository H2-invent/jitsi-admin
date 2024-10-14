/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';
import {createIframe} from "./createConference";
import Swal from "sweetalert2";

global.$ = global.jQuery = $;
import ('jquery-confirm');
let title = "Bestätigung";
let cancel = "Abbrechen";
let ok = "OK";


function initconfirmLoadOpenPopUp() {
    document.addEventListener('click', function (e) {
        if (e.target.classList.contains('adhocConfirm')) {
            e.preventDefault();

            var url = e.target.href;
            var text = e.target.getAttribute('data-text') || 'Wollen Sie die Aktion durchführen?';
            var title = e.target.getAttribute('data-title') || 'Bestätigung';
            var ok = 'Bestätigen'; // Passe den Text nach Bedarf an
            var cancel = 'Abbrechen'; // Passe den Text nach Bedarf an

            fetch(url)
                .then(response => response.text())
                .then(htmlContent => {
                    Swal.fire({
                        title: title,
                        html: htmlContent,
                        backdrop:false,
                        showCancelButton: true,
                        confirmButtonText: ok,
                        cancelButtonText: cancel,
                        customClass: {
                            confirmButton: 'btn btn-outline-danger',
                            cancelButton: 'btn btn-outline-primary',
                        },
                        width: '50%',
                        preConfirm: () => {
                            var selectedOption = document.querySelector('#adhocTag option:checked').getAttribute('data-value');
                            return fetch(selectedOption)
                                .then(response => response.json())
                                .then(data => {
                                    if (data.popups) {
                                        data.popups.forEach(function (value) {
                                            createIframe(value.url, value.title);
                                        });
                                    }
                                })
                                .catch(() => {
                                    Swal.showValidationMessage('Request failed');
                                });
                        }
                    });
                })
                .catch(() => {
                    Swal.fire({
                        title: 'Fehler',
                        text: 'Der Inhalt konnte nicht geladen werden.',
                        icon: 'error'
                    });
                });
        }
    });

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