import $ from 'jquery';
import * as mdb from 'mdb-ui-kit'; // lib
global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'
import {setSnackbar} from './myToastr'
import notificationSound from '../sound/notification.mp3'
import {initAdhocMeeting} from './adhoc'
import {initWebsocket} from './websocket'
import {initPrettyJson} from './jsonBeautifier';

var audio = new Audio(notificationSound);
import {TabUtils} from './tabBroadcast';
import {getCookie} from './cookie';
import {initLayzLoading} from './lazyLoading'
import hotkeys from 'hotkeys-js';
import {initStatus} from "./onlineStatus";
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
    hotkeys('1', function (event, handler) {
        $('#ex1-tab-1-tab').trigger('click');
    });
    hotkeys('2', function (event, handler) {
        $('#ex1-tab-3-tab').trigger('click');
    });
    hotkeys('3', function (event, handler) {
        $('#ex1-tab-2-tab').trigger('click');
    });
    hotkeys('a', function (event, handler) {
        const myModalEl = document.getElementById('modalAdressbook');
        if (!myModalEl.classList.contains('show')) {
            const modal = new mdb.Modal(myModalEl)
            modal.show();
        }
        $('#home-tab').trigger('click');
    });
    hotkeys('g', function (event, handler) {

        const myModalEl = document.getElementById('modalAdressbook');
        if (!myModalEl.classList.contains('show')) {
            const modal = new mdb.Modal(myModalEl)
            modal.show();
        }
        $('#profile-tab').trigger('click');
    });
    hotkeys('n', function (event, handler) {
        $('#createNewConference').trigger('click');
    });
    initWebsocket(websocketTopics);
    initLoadContent();
}

export function wrapOneSelect(ele){
    if (ele && !ele.closest('.selectWrapper')){
        var eleWrap = document.createElement('div');
        eleWrap.classList.add('selectWrapper');
        wrap(ele,eleWrap);
    }
}
function wrapSelect() {
    var select = document.querySelectorAll('select');
    select.forEach(function (ele) {
       wrapOneSelect(ele);
    })
}
function wrap(el, wrapper) {
    el.parentNode.insertBefore(wrapper, el);
    wrapper.appendChild(el);
}


function openBlankTarget(targets) {
    targets.forEach(function (value, i) {
        window.open(value);
    })
}

function initLoadContent() {
    $(document).on('click', '.loadContent', function (e) {
        e.preventDefault();
        var url = $(this).attr('href');
        $('#loadContentModal').load(url, function (data, status) {
            if (status === "error") {
                window.location.reload();
            } else {
                if (!$('#loadContentModal ').hasClass('show')) {
                    $('#loadContentModal').modal('show');
                } else {
                    initNewModal(this);
                }
            }
        });
    });
}


$('#loadContentModal').on('shown.bs.modal', function (e) {
    initNewModal(e)
});

function initNewModal(e) {

    initScheduling();

    $('[data-mdb-toggle="popover"]').popover({html: true});
    $('[data-mdb-toggle="tooltip"]').tooltip('hide');
    $('.tooltip').remove();
    $('[data-mdb-toggle="tooltip"]').tooltip()

    initdateTimePicker('.flatpickr');
    initNewRoomModal();
    $('form').submit(function (event) {
        var btn = $(this).find('button[type=submit]');
        btn.html('<i class="fas fa-spinner fa-spin"></i> ' + btn.text());
        btn.prop("disabled", true)
    });


    $('.generateApiKey').click(function (e) {
        e.preventDefault();
        $('#enterprise_apiKey').val(Math.random().toString(36).substring(2, 15) + Math.random().toString(36).substring(2, 15));
    })
    $('#jwtServer').change(function () {
        if ($('#jwtServer').prop('checked')) {
            $('#appId').collapse('show')
        } else {
            $('#appId').collapse('hide')
        }
    });

    initCopytoClipboard();
    initSearchUser();
    initServerFeatures();
    initRepeater();
    initKeycloakGroups();
    initAddressGroupSearch();
    initChart();
    initPrettyJson();
    wrapSelect();
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new mdb.Input(formOutline).init();
    });
    if (document.getElementById("lineChart") !== null) {
        var ctx = document.getElementById("lineChart").getContext('2d');
        var myChart = new Chart(ctx, {
            type: 'line',
            data: data,
            options: options
        });
    }
}

function initCopytoClipboard() {

    var clipboard = new ClipboardJS('.copyLink');
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
            var feature = data.feature;
            for (var prop in feature) {
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
    var proTip = document.getElementById('proTip')

    if (proTip){
        proTip.style.transform = 'translateY(-'+(proTip.querySelector('.first-line').clientHeight+8+8)+'px)';
    }
}


export {initGenerell, initNewModal, initCopytoClipboard}