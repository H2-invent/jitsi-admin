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

    $div1.load(refreshUrl, function (data, statusTxt) {
        if(statusTxt === "error"){
            window.location.reload();
        }
        var $openDropdown = $('.dropdown-menu.show');
        if ($openDropdown.length === 0) {
            if ($($id1).contents().length !== $(data).find($id1).contents().length){
                $($id1).html($(data).find($id1).contents());
            }
            if ($($id2).contents().length !== $(data).find($id2).contents().length){
                $($id2).html($(data).find($id2).contents());
            }
            $('[data-toggle="popover"]').popover({html: true});
        }


    });
}
export {initRefreshDashboard};
