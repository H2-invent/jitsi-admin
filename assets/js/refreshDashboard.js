import $ from "jquery";
import {initLazyElemt} from './lazyLoading'
import {initStartIframe} from './createConference'
var refreshUrl

function initRefreshDashboard(time, url) {
    setInterval(refreshDashboard, time);
    refreshUrl = url;
}

function refreshDashboard() {
    var $div1 = $('<div>');
    var $id1 = '#ex1-tabs-1';
    var $id2 = '#ex1-tabs-2';
    var $id3 = '#ex1-tabs-3';
    var $id4 = '#favorite-Container';
    var $failures = 0;
    $div1.load(refreshUrl, function (data, statusTxt) {
        if (statusTxt === "error") {
            $failures++;
            if ($failures > 5) {
                window.location.reload();
            }
            return
        }
        var $openDropdown = $('.dropdown-menu.show');

        if ($openDropdown.length === 0) {
            if ($($id1).contents().text() !== $(data).find($id1).contents().text()) {
                console.log('1.7');
                $($id1).html($(data).find($id1).contents());
                initStartIframe();
            }
            if ($($id2 + '-init').contents().text() !== $(data).find($id2 + '-init').contents().text()) {
                console.log('1.8');
                $($id2).html($(data).find($id2).contents());
                initLazyElemt(document.querySelector($id2).querySelector('.lazyLoad'));
            }
            if ($($id3 ).contents().text() !== $(data).find($id3 ).contents().text()) {
                console.log('1.9');
                $($id3).html($(data).find($id3).contents());
                initStartIframe();
            }
            if ($($id4).contents().text() !== $(data).find($id4).contents().text()) {
                console.log('1.10');
                $($id4).html($(data).find($id4).contents());
            }
            $('[data-mdb-toggle="popover"]').popover({html: true});
        }
        $('#actualTime').html($(data).find('#actualTime').contents());
    });
}

export {initRefreshDashboard,refreshDashboard};
