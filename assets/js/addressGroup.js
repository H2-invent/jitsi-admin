import $ from "jquery";

function initAddressGroupSearch() {
    $("#searchAddressGroup").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#addressGroupList li label").filter(function() {
            $(this).closest('li').toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
}
function initListSearch() {
    $(".searchListInput").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        var $list = $(this).closest('.row').siblings('.list-group').find('.breakWord');
        $list.filter(function() {
            $(this).closest('li').toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
}
export {initAddressGroupSearch,initListSearch};
