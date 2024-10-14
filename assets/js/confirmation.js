import Swal from 'sweetalert2'
import {initSearchUser} from './searchUser'

var title = "Bestätigung";
var cancel = "Abbrechen";
var ok = "OK";

function initDirectSend() {
    document.addEventListener('click', function (e) {
        if (e.target.matches('.directSend')) {
            e.preventDefault();
            var url = e.target.href;
            var targetUrl = e.target.dataset.url;
            var target = e.target.dataset.target;

            fetch(url)
                .then(response => response.text())
                .then(data => {
                    document.querySelector(target).closest('div').innerHTML = data;
                    hideTooltip();
                    document.querySelectorAll('[data-mdb-toggle="popover"]').forEach(el => new mdb.Popover(el));
                    document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => new mdb.Tooltip(el));
                    if (data.snack) {
                        document.getElementById('snackbar').textContent = data.text;
                        document.getElementById('snackbar').classList.add('show');
                    }
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
            const targetUrl = e.target.dataset.url;
            const target = e.target.dataset.target;
            const text = e.target.dataset.text || 'Wollen Sie die Aktion durchführen?';

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
            }).then((resul) => {
                if (result.isConfirmed) {
                    fetch(url)
                        .then(response => response.text())
                        .then(data => {
                            document.querySelector(target).closest('div').innerHTML = data;
                            initSearchUser();
                            hideTooltip();
                            document.querySelectorAll('[data-mdb-toggle="popover"]').forEach(el => new mdb.Popover(el));
                            document.querySelectorAll('[data-mdb-toggle="tooltip"]').forEach(el => new mdb.Tooltip(el));
                            if (data.snack) {
                                document.getElementById('snackbar').textContent = data.snack;
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

function hideTooltip() {
    document.querySelectorAll('.tooltip').forEach(el => el.remove());
}

export {initAjaxSend, initDirectSend, initConfirmDirectSendHref, initconfirmHref};