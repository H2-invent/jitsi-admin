import $ from "jquery";

function initAddressGroupSearch() {
    $("#searchAddressGroup").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        $("#addressGroupList li label").filter(function () {
            $(this).closest('li').toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
}

function initListSearch() {
    $(".searchListInput").on("keyup", function () {
        var value = $(this).val().toLowerCase();
        var $list = $(this).closest('.textarea').find('.breakWord');
        $list.filter(function () {
            $(this).closest('li').toggle($(this).text().toLowerCase().indexOf(value) > -1)
        });
    });
    initAddressbook()
}

function initAddressbook() {

    $('.adressbookSearchletter').click(function (e) {
        e.preventDefault();
        $('.adressBookPointOut').removeClass('adressBookPointOut');
        $($(this).data('target')).addClass('adressBookPointOut');
        var position = $($(this).data('target')).offset().top;// - document.getElementById('modalAdressbook').querySelector('.modal-header').clientHeight;
        var textarea = $('#adressbookModalTabContent').find('.textarea')[0];
        var actPosition = textarea.scrollTop;
        var diff = position + actPosition;
        $('#adressbookModalTabContent').find('.textarea').animate({
            scrollTop: diff
        }, 500);
    })
}


export {initAddressGroupSearch, initListSearch};
