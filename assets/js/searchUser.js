import $ from "jquery";
import autosize from "autosize";
import {Dropdown} from 'mdb-ui-kit'; // lib

let timer;              // Timer identifier
const waitTime = 500;   // Wait time in milliseconds

function initSearchUser() {
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
                searchUSer($url,$search);
            }, waitTime);

        })
    }
}

const searchUSer = ($url,$search) => {
    if ($search.length > 0) {
        $.getJSON($url, function (data) {
            var $target = $('#participantUser');
            $target.empty();
            var $user = data.user;
            if ($user.length > 0) {
                $target.append('<i class="fa-solid fa-user fa-2x text-center"></i>');
            }
            for (var i = 0; i < $user.length; i++) {
                var $newUserLine = '<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $user[i].id + '" href="#">' +
                    ($user[i].roles.includes('participant')?'<i class=" text-success fas fa-plus"></i>':'') +
                    ($user[i].roles.includes('moderator')?'<i class="chooseModerator text-success fas fa-crown"  data-mdb-toggle="tooltip" title="Moderator"></i>':'') +
                    '<span>' + $user[i].name + '</span> ' +
                    '</a>'
                $target.append($newUserLine);
            }
            var $group = data.group;
            if ($group.length > 0) {
                $target.append('<i class="fas fa-users fa-2x text-center"></i>');
            }
            for (var i = 0; i < $group.length; i++) {
                $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $group[i].user + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-mdb-toggle="tooltip" title="Moderator"></i> <span><i class="fas fa-users"></i> ' + $group[i].name + '</span></a>');
            }
            $('[data-mdb-toggle="tooltip"]').tooltip('hide');
            $('.tooltip').remove();
            $('[data-mdb-toggle="tooltip"]').tooltip();

            $('.chooseParticipant').mousedown(function (e) {
                e.preventDefault();
                if (!$(this).hasClass('line-indicator')) {
                    var $textarea = $('#new_member_member');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).data('val') + "\n" + data);
                    $('#searchUser').val('');
                    $('#participantsListAdd')
                        .append('<li class="list-group-item">' + $(this).find('span').html() + '</li>')
                        .find('.helpItem').remove();
                    autosize.update($textarea);
                    $(this).removeClass('line-indicator').addClass('line-indicator');
                    let element = $(this);
                    removeElement(this);
                    setTimeout(function (e) {
                        element.removeClass('line-indicator');
                    }, 2000);
                }
            })
            $('.chooseModerator').mousedown(function (e) {
                if (!$(this).hasClass('line-indicator')) {
                    e.preventDefault();
                    e.stopPropagation();
                    $('#moderatorCollapse').collapse('show');
                    var $textarea = $('#new_member_moderator');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).closest('.chooseParticipant').data('val') + "\n" + data);
                    $('#searchUser').val('');
                    $('#moderatorListAdd')
                        .append('<li class="list-group-item">' + $(this).closest('.chooseParticipant').find('span').html() + '</li>')
                        .find('.helpItem').remove();
                    autosize.update($textarea);
                    $(this).removeClass('line-indicator').addClass('line-indicator');
                    let element = $(this);
                    removeElement(this);
                    setTimeout(function (e) {
                        element.removeClass('line-indicator');
                    }, 2000);
                }
            })
        })
    }
}
const removeElement = ($ele) => {
    var el = $ele.closest('.addParticipants')
    el.classList.add('willRemoved');
    setTimeout(function () {
        el.remove();
    },900);
}

export {initSearchUser};
