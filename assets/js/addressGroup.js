import $ from "jquery";
import _ from "lodash/array";
import {getCookie, setCookie} from './cookie'

function initAddressGroupSearch() {
    $("#searchAddressGroup").on("keyup", function () {
        searchUSers(this);
    });
}

function initListSearch() {
    $(".searchListInput").on("keyup", function () {
        searchUSers(this)
    });
    initAddressbook();
    initCategoryFilter();
}

function searchUSers(inputField) {
    var value = $(inputField).val().toLowerCase();
    var $list = $(inputField).closest('.textarea').find('.adressbookline');
    $list.filter(function () {
        if (!$(this).data('indexer')){
            return;
        }
        var indexer = $(this).data('indexer').toLowerCase();
        var res = indexer.indexOf(value) > -1;
        if (!res) {
            this.classList.add('d-none')
        } else {
            this.classList.remove('d-none')
        }
    });
    cleanCapitalLetters();
}

function initAddressbook() {

    $('.adressbookSearchletter').click(function (e) {
        e.preventDefault();
        $('.adressBookPointOut').removeClass('adressBookPointOut');
        $($(this).data('target')).addClass('adressBookPointOut');
        $(this).closest('.registerElement').addClass('adressBookPointOut');
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

    var $checkbox = document.querySelectorAll('.adressBookFilter');

    for (var i = 0; i < $checkbox.length; i++) {

        var tmp = $checkbox[i];
        var $cookie = getCookie(tmp.id);
        if ($cookie === 'true') {
            tmp.checked = true;
        } else {
            tmp.checked = false;
        }
        tmp.addEventListener('change', function (e) {
            var id = this.id;
            setCookie(id, this.checked, 365);
            categorySort();
            e.stopPropagation();
        })
    }
    var $checkboxLine = document.querySelectorAll('.adressBookFilterLine');

    for (var i = 0; i < $checkboxLine.length; i++) {
        var tmp = $checkboxLine[i];
        tmp.addEventListener('click',function (e) {
            e.stopPropagation();
            if (e.srcElement.matches('input')||e.srcElement.matches('label')){
                return null;
            }
            var ele = e.currentTarget.querySelector('input');
            if (ele.checked){
                ele.checked = false;
            }else {
                ele.checked = true;
            }
            categorySort();
        })
    }
    categorySort();
}


function categorySort() {


    var $dot = document.querySelector('.filter-dot');

    var filter = document.querySelectorAll('.adressBookFilter');
    var checked = [];
    var checkcounter = 0;
    for (var i = 0; i < filter.length; i++) {
        var filterEle = JSON.parse(filter[i].dataset.filter);
        if (filter[i].checked) {
            checked.push(filterEle)
            checkcounter++;
        }
    }
    if ($dot) {
        if (checkcounter > 0) {
            $dot.classList.remove('d-none');
            $dot.innerHTML = checkcounter;
        } else {
            $dot.classList.add('d-none')
        }
    }
    var filterArr = checked.length === 0 ? [['all']] : checked
    var content = document.getElementById('adressbookModalTabContent');
    if (!content){
        return false;
    }
    var $list = content.querySelectorAll('.adressbookline');

    for (var k = 0; k < $list.length; k++) {
        try {
            var filterTmp = JSON.parse($list[k].dataset.filterafter);
            var visible = findCommonElements(filterArr, filterTmp);
            if (filterTmp.length === 0) {
                visible = true
            }
            if (!visible) {
                $list[k].classList.add('addressbookCategorieHidden')
            } else {
                $list[k].classList.remove('addressbookCategorieHidden')
            }
        }catch (e) {

        }
    }
    cleanCapitalLetters();
}

function findCommonElements(filter, content) {
    for (var i = 0; i < filter.length; i++) {
        var res = _.intersection(filter[i], content)
        if (res.length === 0) {
            return false;
        }
    }

    return true;
}

function cleanCapitalLetters() {
    var cap = $('.textarea').find('.capital-Letter');
    for (var i = 0; i < cap.length; i++) {
        var next = cap[i].nextElementSibling;
        while (isHidden(next) && !next.classList.contains('capital-Letter')) {
            next = next.nextElementSibling;
            if (!next) {
                break;
            }
        }
        var register = findRegister(cap[i]);
        if (!next || next.classList.contains('capital-Letter')) {
            cap[i].style.display = 'none';
            if (register) {
                register.style.display = 'none';
            }
        } else {
            cap[i].style.removeProperty('display');
            if (register) {
                register.style.removeProperty('display');
            }
        }
    }
}

function isHidden(el) {
    return window.getComputedStyle(el).display === "none";
}

function findRegister(register) {
    try {
        for (const a of register.closest('.adressbookComponent').querySelectorAll('.registerElement ')) {
            if (a.textContent.trim().toLowerCase().includes(register.textContent.trim().toLowerCase())) {
                return a;
            }
        }
    } catch (e) {

    }
}

export {initAddressGroupSearch, initListSearch, categorySort};
