import $ from "jquery";
import autosize from "autosize";


function initSearchUser() {
    autosize($('#new_member_member'));
    autosize($('#new_member_moderator'));
    $('#searchUser').keyup(function (e) {
        var $ele = $(this);
        var $search = $ele.val();
        var $url = $ele.attr('href') + '?search=' + $search;
        if ($search.length > 0) {
            $.getJSON($url, function (data) {
                var $target = $('#participantUser');
                $target.empty();
                var $email = data.user;
                if($email.length > 0){
                    $target.append('<h5>Email</h5>');
                }
                for (var i = 0; i < $email.length; i++) {
                    $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $email[i] + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i> ' + $email[i] + '</a>');
                }
                var $group = data.group;
                console.log($group);
                if($group.length > 0){
                    $target.append('<h5>Gruppe</h5>');
                }
                for (var i = 0; i < $group.length; i++) {
                    $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $group[i].user + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i> ' + $group[i].name + '</a>');
                }
                $('[data-toggle="tooltip"]').tooltip();
                $('.chooseParticipant').click(function (e) {
                    e.preventDefault();
                    var $textarea = $('#new_member_member');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).data('val') + "\n" + data);
                    $('#searchUser').val('');
                    autosize.update($textarea);
                })
                $('.chooseModerator').click(function (e) {
                    e.stopPropagation();
                    $('#moderatorCollapse').collapse('show');
                    var $textarea = $('#new_member_moderator');
                    var data = $textarea.val();
                    $textarea.val('').val($(this).closest('.chooseParticipant').data('val') + "\n" + data);
                    $('#searchUser').val('');
                    autosize.update($textarea);
                })
            })
        }
    })
}

export {initSearchUser};
