import $ from "jquery";
import autosize from "autosize";
import {Dropdown} from 'mdb-ui-kit'; // lib

function initSearchUser() {
var $searchUserField = document.getElementById('searchUser');
    if ($searchUserField !== null) {

        let trigger = document.getElementById('searchUserDropdownTrigger')
        document.getElementById('searchUser').addEventListener("focus", (e)=>{
            Dropdown.getOrCreateInstance(trigger).show()
        })
        document.getElementById('searchUser').addEventListener("blur", (e)=>{
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
            var $search = $ele.val();
            var $url = $ele.attr('href') + '?search=' + $search;

            if ($search.length > 0) {
                $.getJSON($url, function (data) {
                    var $target = $('#participantUser');
                    $target.empty();
                    var $email = data.user;
                    if ($email.length > 0) {
                        $target.append('<h5>E-Mail</h5>');
                    }
                    for (var i = 0; i < $email.length; i++) {
                        $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $email[i].id + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i><span>' + $email[i].name + '</span> </a>');
                    }
                    var $group = data.group;
                    if ($group.length > 0) {
                        $target.append('<h5>Gruppe</h5>');
                    }
                    for (var i = 0; i < $group.length; i++) {
                        $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $group[i].user + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i> <span><i class="fas fa-users"></i> ' + $group[i].name + '</span></a>');
                    }
                    $('[data-toggle="tooltip"]').tooltip();

                    $('.chooseParticipant').mousedown(function (e) {
                        e.preventDefault();

                        var $textarea = $('#new_member_member');
                        var data = $textarea.val();
                        $textarea.val('').val($(this).data('val') + "\n" + data);
                        $('#searchUser').val('');
                        $('#participantsListAdd')
                            .append('<li class="list-group-item">' + $(this).find('span').html() + '</li>')
                            .find('.helpItem').remove();
                        autosize.update($textarea);
                    })
                    $('.chooseModerator').mousedown(function (e) {
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
                    })
                })
            }
        })
    }
}

export {initSearchUser};
