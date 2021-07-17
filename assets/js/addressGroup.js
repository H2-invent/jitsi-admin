import $ from "jquery";

function initAddressGroupSearch(data) {
    $("#searchAddressGroup").on("keyup", function() {
        var value = $(this).val().toLowerCase();
        $("#addressGroupList li label").filter(function() {
            $(this).closest('li').toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
}
export {initAddressGroupSearch};
