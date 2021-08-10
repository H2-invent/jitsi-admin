import $ from "jquery";

function refreshDashboard() {
    var $div1 = $('<div>');
    var $id1 = '#ex1-tabs-1';
    var $id2 = '#ex1-tabs-2';
   refreshDashboardUrl

    $div1.load(refreshDashboardUrl, function (data) {
        var $openDropdown = $('.dropdown-menu.show');
        if ($openDropdown.length === 0) {
            var $oldContent = $($id1).html();
            var $newContent = $(this)[0].find($id1).contents();
            $($id1).html($newContent);
            $oldContent = $($id2).html();
            $newContent = $(this)[0].find($id2).contents();
            $($id2).html($newContent);

        }
    });
}

export {refreshDashboard};
