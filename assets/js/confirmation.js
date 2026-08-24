import Swal from 'sweetalert2'
import {initSearchUser} from './searchUser'
import {Popover, Tooltip, Collapse, Dropdown, Input, initMDB} from "mdb-ui-kit";
import {createIframe} from "./createConference";
import {setSnackbar} from "./myToastr";
import {trans, ADDRESSBOOKERRORTITLE, ADDRESSBOOKCONFIRMTITLE, ADDRESSBOOKCONFIRMTEXT, ADDRESSBOOKCONFIRMCANCEL} from '../translator.js';
import {reloadAddressBookPane} from './addressGroup';

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";


export function initAllComponents() {
    initInput();
    initCollapse();
    initDropdown();
    initTooltip();
    initPopover();
}

export function initPopover() {
    initMDB({Popover});
    const items = document.querySelectorAll('[data-mdb-popover-init]');
    items.forEach(item => {
        Popover.getOrCreateInstance(item);
    });
}

export function initDropdown() {
    initMDB({Dropdown});
    const items = document.querySelectorAll('[data-mdb-dropdown-init]');
    items.forEach(item => {
        Dropdown.getOrCreateInstance(item);
    });
}

export function initCollapse() {
    initMDB({Collapse});
    const items = document.querySelectorAll('[data-mdb-collapse-init]');
    items.forEach(item => {
        Collapse.getOrCreateInstance(item);
    });
}

export function initInput() {
    initMDB({Input});
    const items = document.querySelectorAll('[data-mdb-input-init]');
    items.forEach(item => {
        Input.getOrCreateInstance(item);
    });
}


export function initTooltip() {
    initMDB({Tooltip});
    const items = document.querySelectorAll('[data-mdb-tooltip-init]');
    items.forEach(item => {
        Tooltip.getOrCreateInstance(item);
    });
}

function initDirectSend() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.directSend');

        if (triggerElement) {
            e.preventDefault();
            var url = triggerElement.href;
            var target = triggerElement.dataset.target;
            const targetUrl = triggerElement.dataset.url;
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    if (targetUrl && target){
                        reloadPartial(targetUrl, target);
                    }
                    if (data.snack) {
                        const snackbar = document.getElementById('snackbar');
                        snackbar.textContent = data.text;
                        snackbar.classList.add('show');
                        setTimeout(() => snackbar.classList.remove('show'), 3000); // Snackbar nach 3 Sekunden entfernen
                    }
                });


        }
    });
}


function initconfirmHref() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.confirmHref');

        if (!triggerElement) {
            return;
        }

        e.preventDefault();

        const url = triggerElement.href;
        const ajaxUrl = triggerElement.dataset.ajaxUrl;

        // The confirm text can live on the link itself (data-text) or, for some
        // address book actions, on an inner icon.
        const text = triggerElement.dataset.text
            || triggerElement.querySelector('i')?.dataset?.text
            || (ajaxUrl ? trans(ADDRESSBOOKCONFIRMTEXT, {}, 'ux_message') : 'Wollen Sie die Aktion durchführen?');

        Swal.fire({
            title: ajaxUrl ? trans(ADDRESSBOOKCONFIRMTITLE, {}, 'ux_message') : title,
            text: text,
            icon: 'question',
            backdrop: false,
            showCancelButton: true,
            cancelButtonText: ajaxUrl ? trans(ADDRESSBOOKCONFIRMCANCEL, {}, 'ux_message') : cancel,
            heightAuto: false,
            customClass: {
                confirmButton: 'btn-danger btn',
                cancelButton: 'btn-outline-primary btn'
            }
        }).then((result) => {
            if (!result.isConfirmed) {
                return;
            }

            if (ajaxUrl) {
                confirmHrefAjax(ajaxUrl);
            } else {
                window.location.href = url;
            }
        });
    });
}

// Executes the confirmed AJAX action for a .confirmHref link carrying data-ajax-url.
// The response type decides the follow-up: JSON (e.g. remove contact) triggers a reload
// of the address book pane, HTML (e.g. remove group) replaces the groups tab content.
function confirmHrefAjax(ajaxUrl) {
    fetch(ajaxUrl, { method: 'POST' })
        .then(response => {
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return response.json().then(data => ({ json: data }));
            }
            return response.text().then(html => ({ html }));
        })
        .then(result => {
            if (result.json) {
                if (result.json.error) {
                    Swal.fire({
                        title: trans(ADDRESSBOOKERRORTITLE, {}, 'ux_message'),
                        text: result.json.error,
                        icon: 'error',
                        confirmButtonText: 'OK',
                        confirmButtonColor: '#3085d6',
                    });
                } else {
                    reloadAddressBookPane();
                }
            } else if (result.html) {
                const container = document.getElementById('addressbookContent');
                if (container) {
                    const profilePane = container.querySelector('#profile');
                    if (profilePane) {
                        profilePane.innerHTML = result.html;
                    }
                }
                initMDB({ Popover });
            }
        })
        .catch(() => {});
}

function initconfirmLoadOpenPopUp() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.confirmloadOpenPopUp');

        if (triggerElement) {

            e.preventDefault();
            const url = triggerElement.href;
            const text = triggerElement.dataset.text || 'Wollen Sie die Aktion durchführen?';

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: cancel,
                heightAuto: false,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    const win = window.open('about:blank');
                    fetch(url)
                        .then(response => response.json())
                        .then(data => {

                            if (data.popups) {
                                data.popups.forEach(value => win.location.href = value);
                            }
                            window.location.href = data.redirectUrl;
                        });
                }
            });
        }
    });
}

function initConfirmDirectSendHref() {
    document.addEventListener('click', function (e) {
        // Prüft die DOM-Hierarchie auf ein Element mit der Klasse `.directSendWithConfirm`
        const triggerElement = e.target.closest('.directSendWithConfirm');

        if (triggerElement) {
            e.preventDefault();

            const url = triggerElement.href;
            const text = triggerElement.dataset.text || 'Wollen Sie die Aktion durchführen?';

            const method = triggerElement.dataset.method || 'GET';

            const target = triggerElement.dataset.target;
            const targetUrl = triggerElement.dataset.url;
            Swal.fire({
                title: 'Bestätigung', // Hier ggf. den Titel anpassen
                text: text,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: 'Abbrechen', // Übersetzung anpassen
                heightAuto: false,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url, { method })
                        .then(response => response.json()) // Erwartet eine JSON-Antwort
                        .then(data => {
                            if (targetUrl && target){
                                reloadPartial(targetUrl, target);
                            }

                            if (data.toast){
                                setSnackbar(data.message,'',data.color,false,'0x00',5000);
                            }
                            if (data.snack) {
                                const snackbar = document.getElementById('snackbar');
                                snackbar.textContent = data.text;
                                snackbar.classList.add('show');
                                setTimeout(() => snackbar.classList.remove('show'), 3000); // Snackbar nach 3 Sekunden entfernen
                            }
                        });
                }
            });
        }
    });
}


function initAjaxSend(titleL, cancelL, okL) {
    title = titleL;
    cancel = cancelL;
    ok = okL;
    initConfirmDirectSendHref();
    initDirectSend();
    initconfirmHref();
    initconfirmLoadOpenPopUp();
    initOpenInMultiframe();
}

export function reloadPartial(url, target) {
    fetch(url)
        .then(response => response.text())
        .then(data => {
            // Erstelle ein temporäres DOM-Element, um die HTML-Antwort zu parsen
            const tempDiv = document.createElement('div');
            tempDiv.innerHTML = data;

            // Extrahiere den Inhalt des atendeeList-Elements aus der Antwort
            const newContent = tempDiv.querySelector(target);
            if (newContent) {
                // Aktualisiere den Inhalt von atendeeList im aktuellen DOM
                const oldContent = document.querySelector(target);
                oldContent.innerHTML = newContent.innerHTML; // Setze nur den neuen Inhalt
                initMDB({Collapse, Dropdown, Popover, Tooltip});
                hideTooltip();
                initDropdown();
                initCollapse();
                initPopover();
                initTooltip();
            } else {
                console.error('Das atendeeList-Element wurde in der Antwort nicht gefunden.');
            }


            if (data.snack) {
                document.getElementById('snackbar').textContent = data.text;
                document.getElementById('snackbar').classList.add('show');
            }
        });
}


export function initOpenInMultiframe() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.loadInMultiframe');

        if (triggerElement) {

            e.preventDefault();

            var url = e.target.href;
            return fetch(url)
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


};


function hideTooltip() {
    document.querySelectorAll('.tooltip').forEach(el => el.remove());
}

export {initAjaxSend, initDirectSend, initConfirmDirectSendHref, initconfirmHref};
