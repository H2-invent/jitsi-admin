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
        const triggerElement = e.target.closest('.adhocConfirm');
        if (triggerElement) {
            e.preventDefault();

            var url = triggerElement.href;
            var title = triggerElement.getAttribute('data-title') || 'Bestätigung';
            var ok = 'Bestätigen';
            var cancel = 'Abbrechen';

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
                        heightAuto: false,
                        customClass: {
                            confirmButton: 'btn btn-outline-danger',
                            cancelButton: 'btn btn-outline-primary',
                        },
                        width: '50%',
                        preConfirm: () => {
                            var checkedOption = document.querySelector('#adhocTag option:checked');
                            if (!checkedOption) {
                                Swal.showValidationMessage('No tag selected');
                                return false;
                            }
                            var selectedOption = checkedOption.getAttribute('data-value');
                            return fetch(selectedOption)
                                .then(response => {
                                    if (!response.ok) {
                                        throw new Error('HTTP ' + response.status);
                                    }
                                    return response.json();
                                })
                                .then(data => {
                                    if (data.popups) {
                                        data.popups.forEach(function (value) {
                                            createIframe(value.url, value.title);
                                        });
                                    } else if (data.redirectUrl) {
                                        Swal.showValidationMessage('Die Konferenz konnte nicht erstellt werden.');
                                        return false;
                                    }
                                })
                                .catch((error) => {
                                    Swal.showValidationMessage('Request failed: ' + error.message);
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