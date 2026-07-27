/*
 * Welcome to your app's main JavaScript file!
 *
 */
// Pure CSS imports (bypass sass compiler)
import '@fortawesome/fontawesome-free/css/all.css';
import 'mdb-ui-kit/css/mdb.min.css';
import 'flatpickr/dist/flatpickr.min.css';
import 'jquery-confirm/css/jquery-confirm.css';
import 'h2-invent-apps/css/h2-invent-apps.css';
import 'css-star-rating/css/star-rating.css';
import '@holema/h2datetimepicker/css/dateTimePicker.css';

import '../css/app.scss';
import $ from 'jquery';


global.$ = global.jQuery = $;

import { Dropdown,Popover,Modal,Tooltip,Collapse, initMDB } from "mdb-ui-kit";

import Swal from 'sweetalert2';

import {trans, ADDRESSBOOKERRORTITLE, ADDRESSBOOKERRORDEFAULT, ADDRESSBOOKCONFIRMTITLE, ADDRESSBOOKCONFIRMTEXT, ADDRESSBOOKCONFIRMCANCEL} from '../translator.js';

import ('jquery-confirm');
import * as h2Button from 'h2-invent-apps';
import flatpickr from 'flatpickr';
import autosize from 'autosize';

import {initScheduling} from './scheduling';
import * as Toastr from 'toastr';
import {initCopytoClipboard, initGenerell, initNewModal} from './init';
import {initKeycloakGroups} from './keyCloakGroupsInit';
import {initAddressGroupSearch, initListSearch} from './addressGroup';
import {initSearchUser} from './searchUser';
import {initRefreshDashboard} from './refreshDashboard';
import {initdateTimePicker} from '@holema/h2datetimepicker';
import {initAjaxSend} from './confirmation'
import {attach, init} from 'node-waves'
import {initNewRoomModal} from './newRoom'
import {initTabs, initalSetUnderline} from 'h2-invent-material-tabs'
import {initDashboardnotification} from './dashBoardNotification'
import {initChart} from './chart'
import {Chart} from 'chart.js'


addEventListener('load', function () {
    var param = (new URLSearchParams(window.location.search)).get('modalUrl');
    let url = '';
    if (param !== null) {
        url = atob(param);
    }
    if (typeof (modalUrl) !== 'undefined') {
        url = atob(modalUrl);
    }
    if (url !== null) {
        if (url.startsWith('/')) {
            $('#loadContentModal').load(url, function (data, status) {
                if (status === "error") {
                    window.location.reload();
                } else {
                    $('#loadContentModal ').modal('show');
                }

            });
        }
        let search = new URLSearchParams(window.location.search);
        search.delete('modalUrl');
        let location = window.location.pathname;
        if (search.toString().length > 0) {
            location += '?' + search.toString();
        }

        window.history.pushState({}, document.title, location);
    }
});

$(document).ready(function () {

    initTabs('.nav-mat-tabs');
    attach('.btn', ['waves-effect']);
    attach('.nav-item', ['waves-effect']);
    init();


    initDashboardnotification(topic);

    setTimeout(function () {
        $('.innerOnce').click(function (e) {
            $(this).addClass('d-none');
        })
    }, 500);

    if (importBBB) {
        h2Button.init(bbbUrl);
    }
    if (notificationUrl !== "") {
        h2Button.initNotification(notificationUrl);
    }
    initGenerell();

    initRefreshDashboard(refreshDashboardTime, refreshDashboardUrl)
    initListSearch();
    initAjaxSend(confirmTitle, confirmCancel, confirmOk);

    $('#dismiss, .overlay').on('click', function () {
        // hide sidebar
        $('#sidebar').removeClass('active');
        // hide overlay
        $('.overlay').removeClass('active');
    });

    $('#sidebarCollapse').on('click', function () {
        // open sidebar
        $('#sidebar').addClass('active');
        // fade in the overlay
        $('.overlay').addClass('active');
        $('.collapse.in').toggleClass('in');
        $('a[aria-expanded=true]').attr('aria-expanded', 'false');
    });


    initCopytoClipboard();
    let url = new URLSearchParams(window.location.search);
    url.delete('snack');
    let location = window.location.pathname;
    if (url.toString().length > 0) {
        location += '?' + url.toString();
    }
    window.history.pushState({}, document.title, location);
});
$(window).on('load', function () {
    initMDB({ Popover });
    initMDB({ Dropdown });
    initMDB({ Modal });
    initMDB({ Tooltip });
    initMDB({ Collapse });
    // $('[data-mdb-toggle="popover"]').popover({html: true});
    // $('[data-mdb-toggle="tooltip"]').tooltip('hide');
    // $('.tooltip').remove();
    // $('[data-mdb-toggle="tooltip"]').tooltip();
});

$(document).on('click', '.stopCloseDropdown', function (e) {
    e.stopPropagation();
});



$('#modalAdressbook').on('shown.bs.modal', function (e) {
    initalSetUnderline('#modalAdressbook .underline');
});

// Use escape key to close top-level modal, if no modal is open, do nothing
document.addEventListener('keydown', function (e) {
    if (e.key !== 'Escape') return;

    const visibleModals = [...document.querySelectorAll('.modal.show')];
    if (visibleModals.length > 1) {
        const topModal = visibleModals.reduce((a, b) =>
            (parseInt(getComputedStyle(a).zIndex) || 0) > (parseInt(getComputedStyle(b).zIndex) || 0) ? a : b
        );
        e.stopImmediatePropagation();
        Modal.getInstance(topModal)?.hide();
    }
}, true);




$(".clickable-row").click(function () {
    window.location = $(this).data("href");
});
$('#ex1-tab-3-tab').on('shown.bs.tab', function (e) {

})




$(document).on('click', '.testVideo', function (e) {
    e.preventDefault();
    var $url = $(this).attr('href');
    $url += '?url=' + $('#server_url').val();
    $url += '&cors=' + $('#server_corsHeader').prop('checked');
    window.open($url, '_blank').focus();
})


function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i];
        while (c.charAt(0) == ' ') {
            c = c.substring(1);
        }
        if (c.indexOf(name) == 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}



$('.sidebarToggle').click(function () {
    $('#sidebar').toggleClass('showSidebar');
    $('.sidebarToggle').toggleClass('d-none');

})

// Capture-phase submit handler for #newContactForm.
// Using capture phase (third arg `true`) so this fires before jQuery event handlers,
// preventing initNewModal's generic $('form').submit() spinner handler from also running.
// stopImmediatePropagation() ensures no other submit handlers fire on this event.
document.addEventListener('submit', function (e) {
    const form = e.target.closest('#newContactForm');
    if (!form) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const $btn = $(form).find('button[type=submit]');
    const originalHtml = $btn.html();
    $btn.html('<i class="fas fa-spinner fa-spin"></i> ' + originalHtml);
    $btn.prop('disabled', true);

    fetch(form.dataset.ajaxUrl, {
        method: 'POST',
        body: new FormData(form)
    })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                const loadContentModal = document.getElementById('loadContentModal');
                if (loadContentModal) {
                    const instance = Modal.getInstance(loadContentModal);
                    if (instance) instance.hide();
                }
                reloadAddressBookPane();
            } else if (data.error) {
                Swal.fire({
                    title: trans(ADDRESSBOOKERRORTITLE, {}, 'ux_message'),
                    text: data.error,
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6',
                });
            }
        })
        .catch(() => {})
        .finally(() => {
            // Always restore button state — prevents leftover spinner
            $btn.html(originalHtml);
            $btn.prop('disabled', false);
        });
}, true);

// Capture-phase submit handler for #addressGroupForm.
// Same pattern as #newContactForm above: capture phase + stopImmediatePropagation
// prevents initNewModal's $('form').submit() from duplicating the spinner.
// Response can be JSON (error) or HTML (rendered groups list fragment);
// Content-Type header is checked to determine the response type.
document.addEventListener('submit', function (e) {
    const form = e.target.closest('#addressGroupForm');
    if (!form) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const $btn = $(form).find('button[type=submit]');
    const originalText = $btn.text();
    $btn.html('<i class="fas fa-spinner fa-spin"></i> ' + originalText);
    $btn.prop('disabled', true);

    fetch(form.getAttribute('action'), {
        method: 'POST',
        body: new FormData(form)
    })
        .then(response => {
            const contentType = response.headers.get('content-type') || '';
            if (contentType.includes('application/json')) {
                return response.json().then(data => ({ json: data }));
            }
            return response.text().then(html => ({ html }));
        })
        .then(result => {
            if (result.json) {
                Swal.fire({
                    title: trans(ADDRESSBOOKERRORTITLE, {}, 'ux_message'),
                    text: result.json.error || trans(ADDRESSBOOKERRORDEFAULT, {}, 'ux_message'),
                    icon: 'error',
                    confirmButtonText: 'OK',
                    confirmButtonColor: '#3085d6',
                    allowOutsideClick: false,
                });
            } else if (result.html) {
                const groupsTab = document.getElementById('addressbookContent');
                if (groupsTab) {
                    const profilePane = groupsTab.querySelector('#profile');
                    if (profilePane) {
                        profilePane.innerHTML = result.html;
                    }
                }
                const loadContentModal = document.getElementById('loadContentModal');
                if (loadContentModal) {
                    const instance = Modal.getInstance(loadContentModal);
                    if (instance) instance.hide();
                }
                initMDB({ Popover });
            }
        })
        .catch(() => {})
        .finally(() => {
            $btn.html(originalText);
            $btn.prop('disabled', false);
        });
}, true);

// Capture-phase click handler for confirmHref links with data-ajax-url.
// Fires before confirmation.js's initconfirmHref() handler (which does a full page redirect).
// stopImmediatePropagation() prevents the legacy handler from running on Ajax-enabled links.
// Links without data-ajax-url still fall through to the legacy handler.
document.addEventListener('click', function (e) {
    const trigger = e.target.closest('.confirmHref');
    if (!trigger) return;

    const ajaxUrl = trigger.dataset.ajaxUrl;
    if (!ajaxUrl) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    const text = trigger.querySelector('i')?.dataset?.text || trigger.querySelector('i')?.getAttribute('data-text') || trans(ADDRESSBOOKCONFIRMTEXT, {}, 'ux_message');

    Swal.fire({
        title: trans(ADDRESSBOOKCONFIRMTITLE, {}, 'ux_message'),
        text: text,
        icon: 'question',
        backdrop: false,
        showCancelButton: true,
        cancelButtonText: trans(ADDRESSBOOKCONFIRMCANCEL, {}, 'ux_message'),
        heightAuto: false,
        customClass: {
            confirmButton: 'btn-danger btn',
            cancelButton: 'btn-outline-primary btn'
        }
    }).then((result) => {
        if (result.isConfirmed) {
            fetch(ajaxUrl, { method: 'POST' })
                .then(() => {
                    // Address book entries (favorite/deputy/delete) → reload full address book pane.
                    // Group delete → reload the groups tab only.
                    if (ajaxUrl.includes('adressbook') || ajaxUrl.includes('app_deputy_add_ajax') || ajaxUrl.includes('app_adressbook_favorite_ajax')) {
                        reloadAddressBookPane();
                    } else {
                        fetch(ajaxUrl, { method: 'POST' })
                            .then(response => response.text())
                            .then(html => {
                                const container = document.getElementById('addressbookContent');
                                if (container) {
                                    const profilePane = container.querySelector('#profile');
                                    if (profilePane) {
                                        profilePane.innerHTML = html;
                                    }
                                }
                                initMDB({ Popover });
                            });
                    }
                });
        }
    });
}, true);

// Inline Ajax handler for non-confirmation address book actions (favorite toggle, deputy toggle).
// These links have data-ajax-url but are NOT .confirmHref (handled separately above).
// After the Ajax POST completes, the entire address book pane is reloaded from the server
// because the action may change ordering, favorite status, or deputy status across multiple entries.
document.addEventListener('click', function (e) {
    const link = e.target.closest('a[data-ajax-url]');
    if (!link) return;

    const ajaxUrl = link.dataset.ajaxUrl;
    if (!ajaxUrl) return;

    e.preventDefault();
    e.stopImmediatePropagation();

    if (link.classList.contains('confirmHref')) return;

    const linkIcon = link.querySelector('i');
    if (linkIcon) {
        const originalClass = linkIcon.className;
        linkIcon.className = 'fas fa-spinner fa-spin';
        fetch(ajaxUrl, { method: 'POST' })
            .then(() => reloadAddressBookPane())
            .finally(() => {});
    }
});

// Reloads the address book tab pane (#home) with fresh server-rendered HTML.
//
// The address book pane is replaced wholesale because actions like favorite/deputy toggle
// or contact deletion can affect the ordering, grouping, and cross-entry relationships
// (favorites section, deputy badges, contact grouping by first letter).
//
// Before DOM replacement, all existing MDB component instances (Dropdown, Popover, Tooltip)
// are explicitly disposed. This is critical: without disposal, orphaned MDB instances hold
// references to DOM nodes that no longer exist, causing downstream interactions
// (e.g., clicking the filter dropdown icon) to hang for several seconds while MDB's
// internal state tries to reconcile stale references.
//
// After replacement, each component type is explicitly re-initialized via getOrCreateInstance()
// scoped to the new content. Using initMDB() alone is insufficient: MDB only performs global
// component initialization once per page load, so dynamically inserted elements with
// data-mdb-* attributes would remain uninitialized.
function reloadAddressBookPane() {
    fetch('/room/dashboard/adressbook-fragment')
        .then(response => response.text())
        .then(html => {
            const currentHome = document.getElementById('home');
            if (currentHome) {
                currentHome.querySelectorAll('[data-mdb-dropdown-init]').forEach(el => {
                    Dropdown.getInstance(el)?.dispose();
                });
                currentHome.querySelectorAll('[data-mdb-popover-init]').forEach(el => {
                    Popover.getInstance(el)?.dispose();
                });
                currentHome.querySelectorAll('[data-mdb-tooltip-init]').forEach(el => {
                    Tooltip.getInstance(el)?.dispose();
                });

                currentHome.innerHTML = html;
                initListSearch();
                currentHome.querySelectorAll('[data-mdb-dropdown-init]').forEach(element => {
                    Dropdown.getOrCreateInstance(element);
                });
                currentHome.querySelectorAll('[data-mdb-popover-init]').forEach(element => {
                    Popover.getOrCreateInstance(element);
                });
                currentHome.querySelectorAll('[data-mdb-tooltip-init]').forEach(element => {
                    Tooltip.getOrCreateInstance(element);
                });
            }
        });
}
