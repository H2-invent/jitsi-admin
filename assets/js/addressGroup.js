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
        cleanCapitalLetters();
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
        var textarea = $('#adressbookModalTabContent').find('.content')[0];
        var actPosition = textarea.scrollTop;
        var diff = position
            + actPosition
            - document.getElementById('modalAdressbook').querySelector('.modal-header').clientHeight
            - document.getElementById('modalAdressbook').querySelector('.topbar').clientHeight
            - document.getElementById('modalAdressbook').querySelector('.nav-mat-tabs').clientHeight - 13;

        $('#adressbookModalTabContent').find('.content').animate({
            scrollTop: diff
        }, 500);
    })

}

function initCategoryFilter() {
    var $checkbox = $('.adressBookFilter');
    for (var i = 0; i < $checkbox.length; i++) {

        var tmp = $checkbox[i];
        var $cookie = getCookie(tmp.id) === 'true';
        if ($cookie === true) {
            tmp.setAttribute('checked', 'checked');
        } else {
            tmp.removeAttribute('checked');
        }
    }
    categorySort()
    $checkbox.on('change', function () {
        var id = this.id;
        setCookie(id, $(this).prop('checked'), 365);
        categorySort(this);
    })
}


function categorySort(ele){
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
    var $list = document.getElementById('adressbookModalTabContent').querySelectorAll('.adressbookline');

    for (var k = 0; k < $list.length; k++) {
        var filterTmp = JSON.parse($list[k].dataset.filterafter);
        var visible = findCommonElements3(checked, filterTmp);
        if (filterTmp.length === 0) {
            visible = true
        }
        console.log(visible);
        if (!visible) {
            $list[k].classList.add('addressbookCategorieHidden')
        } else {
            $list[k].classList.remove('addressbookCategorieHidden')
        }
    }
    cleanCapitalLetters();
}

function findCommonElements3(arr1, arr2) {
    return arr1.some(item => arr2.includes(item))
}

function cleanCapitalLetters() {
    var cap = $('.textarea').find('.capital-Letter');
    for (var i = 0; i < cap.length; i++) {
        var next = cap[i].nextElementSibling;
        while (isHidden(next)) {
            next = next.nextElementSibling;
            if (!next) {
                break;
            }
        }
        if (!next || next.classList.contains('capital-Letter')) {
            cap[i].style.display = 'none';
            findRegister(cap[i].textContent).style.display = 'none';

        } else {
            cap[i].style.removeProperty('display');
            findRegister(cap[i].textContent).style.removeProperty('display');
        }
    }
}

function isHidden(el) {
    return window.getComputedStyle(el).display === "none";
}

function findRegister(register) {
    for (const a of document.querySelectorAll('.adressbookSearchletter')) {
        if (a.textContent.includes(register)) {
            return a;
        }
    }
}

export {initAddressGroupSearch, initListSearch};
