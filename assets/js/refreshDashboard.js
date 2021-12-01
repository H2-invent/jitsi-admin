import $ from "jquery";
var refreshUrl
function initRefreshDashboard(time,url){
    setInterval(refreshDashboard,time);
    refreshUrl = url;
}

function refreshDashboard() {
    var $div1 = $('<div>');
    var $id1 = '#ex1-tabs-1';
    var $id2 = '#ex1-tabs-2';
    var $id3 = '#ex1-tabs-3';

    $div1.load(refreshUrl, function (data, statusTxt) {
        if(statusTxt === "error"){
            window.location.reload();
        }

        var $openDropdown = $('.dropdown-menu.show');
        console.log('1.3');
        if ($openDropdown.length === 0) {
            console.log('1.4')
            if ($($id1).contents().length !== $(data).find($id1).contents().length){
                console.log('1.5')
                $($id1).html($(data).find($id1).contents());
            }
            if ($($id2).contents().length !== $(data).find($id2).contents().length){
                console.log('1.6')
                $($id2).html($(data).find($id2).contents());
            }
            if ($($id3).contents().length !== $(data).find($id3).contents().length){
                console.log('1.7')
                $($id3).html($(data).find($id3).contents());
            }
            $('[data-toggle="popover"]').popover({html: true});
        }
        $('#actualTime').html($(data).find('#actualTime').contents());
    });
}
export {initRefreshDashboard};
