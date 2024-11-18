import Swal from 'sweetalert2'
import {initSearchUser} from './searchUser'
import {Popover, Tooltip, Collapse, Dropdown, Input, initMDB} from "mdb-ui-kit";

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initDirectSend() {
    document.addEventListener('click', function (e) {
        if (e.target.matches('.directSend')) {
            e.preventDefault();
            var url = e.target.href;
            var target = e.target.dataset.target;
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    reloadPartial(targetUrl,target);
                    if (data.snack) {
                        document.getElementById('snackbar').textContent = data.text;
                        document.getElementById('snackbar').classList.add('show');
                    }
                });


        }
    });
}

export function initAllComponents(){
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
        if (e.target.matches('.confirmHref')) {
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
        if (e.target.matches('.confirmloadOpenPopUp')) {
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
        if (e.target.matches('.directSendWithConfirm')) {
            e.preventDefault();
            const url = e.target.href;
            const target = e.target.dataset.target;
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
                    fetch(url)
                        .then(response => response.text())
                        .then(data => {
                            reloadPartial(targetUrl,target);
                            if (data.snack) {
                                document.getElementById('snackbar').textContent = data.text;
                                document.getElementById('snackbar').classList.add('show');
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
}

export function reloadPartial(url,target){
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
function hideTooltip() {
    document.querySelectorAll('.tooltip').forEach(el => el.remove());
}

export {initAjaxSend, initDirectSend, initConfirmDirectSendHref, initconfirmHref};