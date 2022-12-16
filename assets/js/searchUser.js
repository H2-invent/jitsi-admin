import $ from "jquery";
import autosize from "autosize";
import {Dropdown} from 'mdb-ui-kit'; // lib

let timer;              // Timer identifier
const waitTime = 200;   // Wait time in milliseconds
var newParticipant = [];
var newModerator = [];

function initSearchUser() {
    newParticipant = [];
    newModerator = [];
    var $searchUserField = document.getElementById('searchUser');
    if ($searchUserField !== null) {

        let trigger = document.getElementById('searchUserDropdownTrigger')
        document.getElementById('searchUser').addEventListener("focus", (e) => {
            Dropdown.getOrCreateInstance(trigger).show()
        })
        document.getElementById('searchUser').addEventListener("blur", (e) => {
            Dropdown.getOrCreateInstance(trigger).hide()
        })
        autosize($('#new_member_member'));
        autosize($('#new_member_moderator'));
        $('.defaultSearch').mousedown(function (e) {
            e.preventDefault();
            e.stopPropagation();
        })
        $('#searchUser').keyup(function (e) {
            var $ele = $(this);
            const $search = $ele.val();
            const $url = $ele.attr('href') + '?search=' + $search;
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchUSer($url, $search);
            }, waitTime);

        })
    }
    $('#form_participant_form').submit(function() {

            for (var i in newParticipant){
                var $textarea = $('#new_member_member');
                var data = $textarea.val();
                $textarea.val(newParticipant[i].uid + "\n" + data);
            }
            for (var i in newModerator){
                var $textarea = $('#new_member_moderator');
                var data = $textarea.val();
                $textarea.val(newModerator[i].uid + "\n" + data);
            }

        return true; // return false to cancel form action
    });
}

const searchUSer = ($url, $search) => {
    if ($search.length > 0) {
        $.getJSON($url, function (data) {
            var $target = $('#participantUser');
            $target.empty();
            var $user = data.user;
            if ($user.length > 0) {
                $target.append('<i class="fa-solid fa-user fa-2x text-center"></i>');
            }
            for (var i = 0; i < $user.length; i++) {
                var $newUserLine = '<a class="dropdown-item chooseParticipant addParticipants" data-id="user_'+$user[i].id+'" data-val="' + $user[i].id + '" href="#">' +
                    ($user[i].roles.includes('participant') ? '<i class=" text-success fas fa-plus"></i>' : '') +
                    ($user[i].roles.includes('moderator') ? '<i class="chooseModerator text-success fas fa-crown"  data-mdb-toggle="tooltip" title="Moderator"></i>' : '') +
                    '<span>' + $user[i].name + '</span> ' +
                    '</a>'
                $target.append($newUserLine);
            }
            var $group = data.group;
            if ($group.length > 0) {
                $target.append('<i class="fas fa-users fa-2x text-center"></i>');
            }
            for (var i = 0; i < $group.length; i++) {
                $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-id="groupd_'+$group[i].id+'" data-val="' + $group[i].user + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-mdb-toggle="tooltip" title="Moderator"></i> <span><i class="fas fa-users"></i> ' + $group[i].name + '</span></a>');
            }
            $('[data-mdb-toggle="tooltip"]').tooltip('hide');
            $('.tooltip').remove();
            $('[data-mdb-toggle="tooltip"]').tooltip();

            $('.chooseParticipant').mousedown(function (e) {
                e.preventDefault();
                if (!$(this).hasClass('line-indicator')) {

                    var ele = this;
                    setInput(ele,'#new_member_member','#participantsListAdd', newParticipant);
                    document.getElementById('searchUser').value = '';

                }
            })
            $('.chooseModerator').mousedown(function (e) {
                if (!$(this).hasClass('line-indicator')) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('#moderatorCollapse').collapse('show');

                    var ele = this.closest('.chooseParticipant');
                    setInput(ele,'#new_member_moderator','#moderatorListAdd', newModerator);
                    document.getElementById('searchUser').value = '';


                }
            })
        })
    }
}

function setInput(ele, textfield,listfield,array){

    var uid = ele.dataset.val;
    var textarea = document.querySelector(textfield);
    var data = textarea.value;

    if (!textarea.closest('.row').classList.contains('d-none')) {
        textarea.value = '';
        textarea.value = uid + "\n" + data;
    }else {
        var id = ele.dataset.id;
        var listName = ele.querySelector('span').innerHTML;
        array[id] = {uid: uid, name: listName};
        setParticipantList(array, listfield);
    }


    ele.classList.add('line-indicator');
    ele.addEventListener('animationend', () => {
        ele.remove();
    });
    autosize.update(textarea);
}

function setParticipantList(list, listToAdd) {

    document.querySelector(listToAdd).innerHTML = ''
    for (var $i in list) {
        $(listToAdd)
            .append('<li class="list-group-item  d-flex justify-content-between align-items-center"><span>' + list[$i].name + '</span><i class="fa fa-trash removeParticipant" data-uid="' + $i + '"></i> </li>')
            .find('.helpItem').remove();
    }

    $(listToAdd).find('.removeParticipant').click(function () {
        var uid = $(this).data('uid');
        delete list[uid];
        setParticipantList(list, listToAdd)
    })
}

export {initSearchUser};
