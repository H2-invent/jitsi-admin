import $ from "jquery";
import flatpickr from 'flatpickr'

var reload = '';

function initScheduling() {
    $('.addSchedule').click(function (e) {
        e.preventDefault();
        var $date = $(this).closest('.input-group').find('input').val();
        reload = $(this).data('reload');
        if($date != ''){
            $.post($(this).data('url'), {date: $date}, function (data) {
                if (data.error == false) {
                    $('#scheduleSlots').load(reload + ' #slot', function () {
                        initRemove();
                        $('#schedulePickr').val('');
                        $('#schedulePickr').flatpickr({
                            minDate: "today",
                            enableTime: true,
                            time_24hr: true,
                            defaultMinute: 0,
                            minuteIncrement: 15,
                            altFormat: 'd.m.Y H:i',
                            dateFormat: 'Y-m-d H:i',
                            altInput: true
                        });

                    })

                }
            });
        }

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
        $.post($url, {user: $user, type: $type, time: $schedule}, function (data) {
            snackbar(data.text);
        })
    })
}

function snackbar($text) {
    $('#snackbar').text($text).addClass('show').removeClass('d-none');
    setTimeout(function () {
        $('#snackbar').removeClass('show').addClass('d-none');
    }, 3000);

}

export {initScheduling, initSchedulePublic};
