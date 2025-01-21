import Swal from 'sweetalert2'
import {initSearchUser} from './searchUser'
import {Popover, Tooltip, Collapse, Dropdown, Input, initMDB} from "mdb-ui-kit";
import {createIframe} from "./createConference";
import {setSnackbar} from "./myToastr";

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initDirectSend() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.directSend');

        if (triggerElement) {
            e.preventDefault();
            var url = e.target.href;
            var target = e.target.dataset.target;
            const targetUrl = e.target.dataset.url;
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    reloadPartial(targetUrl, target);
                    if (data.snack) {
                        document.getElementById('snackbar').textContent = data.text;
                        document.getElementById('snackbar').classList.add('show');
                    }
                });


        }
    });
}

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

function initconfirmHref() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.confirmHref');

        if (triggerElement) {
            e.preventDefault();
            const url = e.target.href;
            const text = e.target.dataset.text || 'Wollen Sie die Aktion durchführen?';

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: cancel,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = url;
                }
            });
        }
    });
}

function initconfirmLoadOpenPopUp() {
    document.addEventListener('click', function (e) {
        const triggerElement = e.target.closest('.confirmloadOpenPopUp');

        if (triggerElement) {

            e.preventDefault();
            const url = e.target.href;
            const text = e.target.dataset.text || 'Wollen Sie die Aktion durchführen?';

            Swal.fire({
                title: title,
                text: text,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: cancel,
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
            const target = triggerElement.dataset.target;
            const targetUrl = triggerElement.dataset.url;
            const text = triggerElement.dataset.text || 'Wollen Sie die Aktion durchführen?';

            Swal.fire({
                title: 'Bestätigung', // Hier ggf. den Titel anpassen
                text: text,
                icon: 'question',
                backdrop: false,
                showCancelButton: true,
                cancelButtonText: 'Abbrechen', // Übersetzung anpassen
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton: 'btn-outline-primary btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url)
                        .then(response => response.json()) // Erwartet eine JSON-Antwort
                        .then(data => {
                            reloadPartial(targetUrl, target);
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