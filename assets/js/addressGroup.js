import $ from "jquery";

import {getCookie, setCookie} from './cookie'

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
        var $list = $(this).closest('.textarea').find('.adressbookline');
        $list.filter(function () {
            var indexer = $(this).data('indexer').toLowerCase();
            var res = indexer.indexOf(value) > -1;
            console.log(res);
            if (!res) {
                this.classList.add('addressbookSearchHidden')
            } else {
                this.classList.remove('addressbookSearchHidden')
            }
        });
    });
    initAddressbook();
    initCategoryFilter();
}

function initAddressbook() {

    $('.adressbookSearchletter').click(function (e) {
        e.preventDefault();
        $('.adressBookPointOut').removeClass('adressBookPointOut');
        $($(this).data('target')).addClass('adressBookPointOut');
        var position = $($(this).data('target')).offset().top;
        var textarea = $('#adressbookModalTabContent').find('.textarea')[0];
        var actPosition = textarea.scrollTop;
        var diff = position + actPosition;
        $('#adressbookModalTabContent').find('.textarea').animate({
            scrollTop: diff
        }, 500);
    })

}

function initCategoryFilter() {
    $('.adressBookFilter').on('change', function () {
        var filter = $('.adressBookFilter');
        var checked = [];
        var unchecked = [];
        for (var i = 0; i < filter.length; i++) {
            var filterEle = JSON.parse(filter[i].dataset.filter);
            if ($(filter[i]).prop('checked')) {
                checked = checked.concat(filterEle)
            } else {
                unchecked = unchecked.concat(filterEle)
            }
        }
        var $list = $(this).closest('.textarea').find('.adressbookline');

        for (var k = 0; k < $list.length; k++) {
            var filterTmp= JSON.parse($list[k].dataset.filterafter);
            var visible = findCommonElements3(checked,filterTmp);
            if (filterTmp.length === 0){
                visible = true
            }
            console.log(visible);
            if (!visible){
                $list[k].classList.add('addressbookCategorieHidden')
            }else {
                $list[k].classList.remove('addressbookCategorieHidden')
            }
        }

    })
}
function findCommonElements3(arr1, arr2) {
    return arr1.some(item => arr2.includes(item))
}

export {initAddressGroupSearch, initListSearch};
