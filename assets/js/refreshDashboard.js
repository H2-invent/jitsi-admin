import $ from "jquery";
import {initLazyElemt} from './lazyLoading'
import {initStartIframe} from './createConference'
import {initAllComponents, initCollapse, initDropdown, initPopover, initTooltip} from "./confirmation";

var refreshUrl

function initRefreshDashboard(time, url) {
    setInterval(refreshDashboard, time);
    refreshUrl = url;
}

function initLazyLoads($container) {
    $container.find('.lazyLoad').each(function () {
        initLazyElemt(this);
    });
}

function refreshDashboard() {
    var $id1 = '#ex1-tabs-1';
    var $id2 = '#ex1-tabs-2';
    var $id3 = '#ex1-tabs-3';
    var $id4 = '#favorite-Container';
    var $failures = 0;
    $.get(refreshUrl, function (data, statusTxt) {
        if (statusTxt === "error") {
            $failures++;
            if ($failures > 5) {
                window.location.reload();
            }
            return
        }
        // Parse the response without inserting it into the document, so that the images
        // it contains (e.g. profile pictures in the address book) are not re-downloaded.
        var $doc = $(data);
        var $openDropdown = $('.dropdown-menu.show');

        // The favorites sidebar is always kept fresh, even after lazy loading has started:
        // it has no lazy-loaded children and no scroll-position dependency, so replacing
        // its HTML is safe and the refresh does not disturb the loaded conference pages.
        if ($($id4).contents().text() !== $doc.find($id4).contents().text()) {
            console.log('1.10');
            $($id4).html($doc.find($id4).contents());
        }
        initAllComponents();

        // Once the user has lazy-loaded additional conferences, the tab-body refresh is
        // skipped entirely: it would reset the loaded pages/scroll position and re-fetch
        // the whole page (including every image) just to update content the user is no
        // longer looking at.
        if (window.dashboardLazyLoaded === true) {
            return;
        }

        if ($openDropdown.length === 0) {
            if ($($id1).contents().text() !== $doc.find($id1).contents().text()) {
                console.log('1.7');
                $($id1).html($doc.find($id1).contents());
                initStartIframe();
                initLazyLoads($($id1));
            }
            if ($($id2 + '-init').contents().text() !== $doc.find($id2 + '-init').contents().text()) {
                console.log('1.8');
                $($id2).html($doc.find($id2).contents());
                initLazyLoads($($id2));
            }
            if ($($id3).contents().text() !== $doc.find($id3).contents().text()) {
                console.log('1.9');
                $($id3).html($doc.find($id3).contents());
                initStartIframe();
                initLazyLoads($($id3));
            }
        }
        $('#actualTime').html($doc.find('#actualTime').contents());
    });
}

export {initRefreshDashboard, refreshDashboard};
