import $ from 'jquery';
import {Popover, Modal, Input, initMDB} from "mdb-ui-kit";

global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'
import {initAdhocMeeting} from './adhoc'
import {initWebsocket} from './websocket'
import {initPrettyJson} from './jsonBeautifier';
import {initLayzLoading} from './lazyLoading'
import hotkeys from 'hotkeys-js';
import {inIframe} from "./moderatorIframe";
import {initScheduling} from "./scheduling";
import {initdateTimePicker} from "@holema/h2datetimepicker";
import {initNewRoomModal} from "./newRoom";
import {initSearchUser} from "./searchUser";
import {initKeycloakGroups} from "./keyCloakGroupsInit";
import {initAddressGroupSearch} from "./addressGroup";
import {initChart} from "./chart";
import {Chart} from "chart.js";
import ClipboardJS from "clipboard";
import {initStartIframe} from "./createConference";
import {checkFirefox} from "./checkFirefox";
import {showAppIdSettings, showLiveKitServerSettings} from "./serverSettings";
import {initCollapse, initDropdown, initInput, initPopover, initTooltip} from "./confirmation";

function initGenerell() {
    checkFirefox();
    Push.Permission.request();
    initDarkmodeSwitch();
    initLayzLoading();
    initStartIframe();
    initProtip();
    wrapSelect();
    if (inIframe()) {
        document.body.classList.add("in-iframe");
    }
    if (window.innerWidth < 768) {
        document.body.classList.add("in-smartPhone");
    }
    openBlankTarget(blankTarget);
    initAdhocMeeting(confirmTitle, confirmCancel, confirmOk);
    hotkeys('1', function () {
        $('#ex1-tab-1-tab').trigger('click');
    });
    hotkeys('2', function () {
        $('#ex1-tab-3-tab').trigger('click');
    });
    hotkeys('3', function () {
        $('#ex1-tab-2-tab').trigger('click');
    });
    hotkeys('a', function () {
        const myModalEl = document.getElementById('modalAdressbook');
        if (!myModalEl.classList.contains('show')) {
            const modal = new Modal(myModalEl)
            modal.show();
        }
        $('#home-tab').trigger('click');
    });
    hotkeys('g', function () {

        const myModalEl = document.getElementById('modalAdressbook');
        if (!myModalEl.classList.contains('show')) {
            const modal = new Modal(myModalEl)
            modal.show();
        }
        $('#profile-tab').trigger('click');
    });
    hotkeys('n', function () {
        $('#createNewConference').trigger('click');
    });
    initWebsocket(websocketTopics);
    initLoadContent();
}

export function wrapOneSelect(ele) {
    if (ele && !ele.closest('.selectWrapper')) {
        const eleWrap = document.createElement('div');
        eleWrap.classList.add('selectWrapper');
        wrap(ele, eleWrap);
    }
}

function wrapSelect() {
    const select = document.querySelectorAll('select');
    select.forEach(function (ele) {
        wrapOneSelect(ele);
    })
}

function wrap(el, wrapper) {
    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);
}


function openBlankTarget(targets) {
    targets.forEach(function (value) {
        window.open(value);
    })
}

function initLoadContent() {
    document.addEventListener('click', function (e) {
        // Suche das nächste übergeordnete <a>-Element (einschließlich des Targets selbst)
        let target = e.target;
        while (target && target !== document) {
            if (target.tagName === 'A') break; // Wenn es ein <a>-Element ist, abbrechen
            target = target.parentElement; // Gehe im DOM nach oben
        }

        // Prüfe, ob ein <a>-Element gefunden wurde und ob es die Klasse 'loadContent' hat
        if (target && target.tagName === 'A' && target.classList.contains('loadContent')) {
            e.preventDefault(); // Verhindere die Standardaktion des Links

            const url = target.getAttribute('href');

            fetch(url)
                .then(response => {
                    if (!response.ok) throw new Error('Network response was not ok');
                    return response.text();
                })
                .then(data => {
                    const modalElement = document.getElementById('loadContentModal');
                    modalElement.innerHTML = data;

                    // Überprüfe, ob das Modal geöffnet ist
                    if (!modalElement.classList.contains('show')) {
                        const modal = Modal.getOrCreateInstance(modalElement);
                        modal.show();
                    } else {
                        initNewModal(modalElement);
                    }
                })
                .catch(() => {
                    window.location.reload();
                });
        }
    });
}

$('#loadContentModal').on('shown.bs.modal', function (e) {
    initNewModal(e)
});

function initNewModal() {

    initScheduling();
    initMDB({Popover});
    $('.tooltip').remove();
    initDropdown();
    initTooltip();
    initPopover();
    initCollapse();
    initInput();
    // $('[data-mdb-toggle="popover"]').popover({html: true});
    // $('[data-mdb-toggle="tooltip"]').tooltip('hide');
    //
    // $('[data-mdb-toggle="tooltip"]').tooltip()

    initdateTimePicker('.flatpickr');
    initNewRoomModal();
    $('form').submit(function () {
        const btn = $(this).find('button[type=submit]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> ' + btn.text());
        btn.prop("disabled", true)
    });


    $('.generateApiKey').click(function (e) {
        e.preventDefault();
        $('#enterprise_apiKey').val(Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15));
    })
    showAppIdSettings();
    showLiveKitServerSettings();


    initCopytoClipboard();
    initSearchUser();
    initServerFeatures();
    initRepeater();
    initKeycloakGroups();
    initAddressGroupSearch();
    initChart();
    initPrettyJson();
    wrapSelect();
    initMDB({Input});

    if (document.getElementById("lineChart") !== null) {
        const ctx = document.getElementById("lineChart").getContext('2d');
         new Chart(ctx, {
            type: 'line',
            data: data,
            options: options
        });
    }
}

function initCopytoClipboard() {

    new ClipboardJS('.copyLink');
}

function initServerFeatures() {
    getMoreFeature($('.moreFeatures').val())
    $('.moreFeatures').change(function () {
        getMoreFeature($(this).val());
    })
}

function getMoreFeature(id) {
    if (typeof id !== 'undefined') {
        $.getJSON(moreFeatureUrl, 'id=' + id, function (data) {
            const feature = data.feature;
            for (const prop in feature) {
                if (feature[prop] == true) {
                    $('#' + prop).removeClass('d-none')
                } else {
                    $('#' + prop).addClass('d-none')
                }
            }
        })
    }
}

function initRepeater() {
    $('.repeater').addClass('d-none');
    $('#repeater_' + $('#repeater_repeatType').val()).removeClass('d-none');

    $('#repeater_repeatType').change(function () {

        $('.repeater').addClass('d-none');
        $('#repeater_' + $(this).val()).removeClass('d-none');
    })
}

function initProtip() {
    const proTip = document.getElementById('proTip')

    if (proTip) {
        proTip.style.transform = 'translateY(-' + (proTip.querySelector('.first-line').clientHeight + 8 + 8) + 'px)';
    }
}


export {initGenerell, initNewModal, initCopytoClipboard}