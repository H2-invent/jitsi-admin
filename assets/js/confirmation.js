import Swal from 'sweetalert2'
import {initSearchUser} from './searchUser'
import * as mdb from 'mdb-ui-kit'; // lib
var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initDirectSend() {
    document.addEventListener('click', function (e) {
        if (e.target.matches('.directSend')) {
            e.preventDefault();
            var url = e.target.href;
            var target = e.target.dataset.target;
            var targetUrl = e.target.dataset.url;
            fetch(url)
                .then(response => response.text())
                .then(data => {
                    replaceContent(target,targetUrl)
                });
        }
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
                backdrop:false,
                showCancelButton: true,
                cancelButtonText: cancel,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton:  'btn-outline-primary btn'
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
                backdrop:false,
                showCancelButton: true,
                cancelButtonText: cancel,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton:  'btn-outline-primary btn'
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
            const targetUrl = e.target.dataset.url;

            Swal.fire({
                title: 'Sind Sie sicher?',
                text: text,
                icon: 'question',
                backdrop:false,
                showCancelButton: true,
                cancelButtonText: cancel,
                customClass: {
                    confirmButton: 'btn-danger btn',
                    cancelButton:  'btn-outline-primary btn'
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    fetch(url)
                        .then(response => {
                            if (!response.ok) {
                                throw new Error(`HTTP error! status: ${response.status}`);
                            }
                        })
                        .then(data => {
                         replaceContent(target,targetUrl)
                            if (data.snack !== undefined) {
                                const snackbar = document.getElementById('snackbar');
                                if (snackbar) {
                                    snackbar.textContent = data.snack;
                                    snackbar.classList.add('show');
                                }
                            }

                            document.querySelectorAll('[data-mdb-toggle="popover"]').forEach(el => {
                                new mdb.Popover(el, { html: true });
                            });
                            document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => {
                                const tooltipInstance = mdb.Tooltip.getInstance(el);
                                if (tooltipInstance) tooltipInstance.hide();
                            });
                            document.querySelectorAll('.tooltip').forEach(el => el.remove());
                            document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => {
                                new mdb.Tooltip(el);
                            });
                        })
                        .catch(error => {
                            console.error('Error:', error);
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

function hideTooltip() {
    document.querySelectorAll('.tooltip').forEach(el => el.remove());
}

function replaceContent(target,targetUrl) {
    const parentDiv = document.querySelector(target).closest('div');
    fetch(targetUrl)
        .then(response => {
            if (!response.ok) {
                throw new Error(`HTTP error! status: ${response.status}`);
            }
            return response.text();
        })
        .then(html => {
            const parser = new DOMParser();
            const doc = parser.parseFromString(html, 'text/html');
            const targetContent = doc.querySelector(target);

            if (targetContent) {
                parentDiv.innerHTML = targetContent.outerHTML;
            }
            initSearchUser();
            hideTooltip();
            document.querySelectorAll('[data-mdb-toggle="popover"]').forEach(el => {
                new mdb.Popover(el, { html: true });
            });
            document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => {
                const tooltipInstance = mdb.Tooltip.getInstance(el);
                if (tooltipInstance) tooltipInstance.hide();
            });
            document.querySelectorAll('.tooltip').forEach(el => el.remove());
            document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => {
                new mdb.Tooltip(el);
            });
        });


}
export {initAjaxSend, initDirectSend, initConfirmDirectSendHref, initconfirmHref};