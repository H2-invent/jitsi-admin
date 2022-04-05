import $ from "jquery";

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
    $div1.load(refreshUrl, function (data, statusTxt) {
        if (statusTxt === "error") {
            window.location.reload();
        }
        var $openDropdown = $('.dropdown-menu.show');

        if ($openDropdown.length === 0) {
            if ($($id1).contents().text() !== $(data).find($id1).contents().text()) {
                console.log('1.7');
                $($id1).html($(data).find($id1).contents());
            }
            if ($($id2).contents().text() !== $(data).find($id2).contents().text()) {
                console.log('1.8');
                $($id2).html($(data).find($id2).contents());
            }
            if ($($id3).contents().text() !== $(data).find($id3).contents().text()) {
                console.log('1.9');
                $($id3).html($(data).find($id3).contents());
            }
            if ($($id4).contents().text() !== $(data).find($id4).contents().text()) {
                console.log('1.10');
                $($id4).html($(data).find($id4).contents());
            }
            $('[data-toggle="popover"]').popover({html: true});
        }
        $('#actualTime').html($(data).find('#actualTime').contents());
    });
}

export {initRefreshDashboard};
