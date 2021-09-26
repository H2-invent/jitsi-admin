import $ from "jquery";
import autosize from "autosize";


function initSearchUser() {
    if($('#searchUser') !== null) {
        $('#searchUser').focus(function () {
            $(this).next('.dropdown-menu').dropdown('show');
            $(document).on("click", function (event) {
                var $trigger = $(".dropdown");
                if ($trigger !== event.target && !$trigger.has(event.target).length) {
                    $(".dropdown-menu").dropdown("hide");
                }
            });
        })

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
                    if ($email.length > 0) {
                        $target.append('<h5>Email</h5>');
                    }
                    for (var i = 0; i < $email.length; i++) {
                        $target.append('<a class="dropdown-item chooseParticipant addParticipants" data-val="' + $email[i].id + '" href="#"><i class=" text-success fas fa-plus"></i><i class="chooseModerator text-success fas fa-crown"  data-toggle="tooltip" title="Moderator"></i> ' + $email[i].name + '</a>');
                    }
                    var $group = data.group;
                    if ($group.length > 0) {
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
                        $('#participantsListAdd')
                            .append('<li class="list-group-item">' + $(this).text() + '</li>')
                            .find('.helpItem').remove();
                        autosize.update($textarea);
                    })
                    $('.chooseModerator').click(function (e) {
                        e.stopPropagation();
                        $('#moderatorCollapse').collapse('show');
                        var $textarea = $('#new_member_moderator');
                        var data = $textarea.val();
                        $textarea.val('').val($(this).closest('.chooseParticipant').data('val') + "\n" + data);
                        $('#searchUser').val('');
                        $('#moderatorListAdd')
                            .append('<li class="list-group-item">' + $(this).closest('.chooseParticipant').text() + '</li>')
                            .find('.helpItem').remove();
                        autosize.update($textarea);
                    })
                })
            }
        })
    }
}

export {initSearchUser};
