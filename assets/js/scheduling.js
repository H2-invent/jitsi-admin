
import $ from "jquery";


var reload = '';

function initScheduling() {
    $('.addSchedule').click(function (e) {
        e.preventDefault();
        var $date = $(this).closest('.input-group').find('input').val();
        reload = $(this).data('reload');
        $.post($(this).data('url'), {date: $date}, function (data) {
            if (data.error == false) {
                $('#scheduleSlots').load(reload + ' #slot', function () {
                    initRemove();

                })

            }
        });
    })
    initRemove();
}

function initRemove() {
    $('.removeSchedule').click(function (e) {
        e.preventDefault();
        reload = $(this).data('reload');
        $.ajax({
            url: $(this).data('url'),
            type: 'DELETE',
            success: function (data) {
                if (data.error == false) {
                    $('#scheduleSlots').load(reload + ' #slot', function () {
                            initRemove();
                            initFlatpickr();
                        }
                    )
                }
            }
        });
    })
}

function initSchedulePublic() {
    $('.scheduleSelect').change(function (e) {
        var $schedule = $(this).data('schedule');
        var $user = $(this).data('uid');
        var $type = $(this).data('type');
        var $url = $(this).data('url');
        $.post($url,{user:$user,type:$type,time: $schedule},function (data){
            snackbar(data.text);
        })
    })
}
function snackbar($text){
    $('#snackbar').text($text).addClass('show').removeClass('d-none');
    setTimeout(function () {
        $('#snackbar').removeClass('show').addClass('d-none');
    }, 3000);

}
export {initScheduling,initSchedulePublic};
