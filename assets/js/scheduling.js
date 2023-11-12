import $ from "jquery";
import {initdateTimePicker,cleanDateTimePicker} from '@holema/h2datetimepicker';

var reload = '';

function initScheduling() {
    initdateTimePicker('#schedulePickr')

    var $ele = document.getElementById('scheduleAppendBtn');
    if ($ele === null){
        return;
    }
    $ele.parentNode.querySelector('.input-group').appendChild($ele);
    $('.addSchedule').click(function (e) {
        e.preventDefault();

        var $date = $('#schedulePickr').val();
        reload = $(this).data('reload');
        if($date != ''){
            $.post($(this).data('url'), {date: $date}, function (data) {
                if (data.error == false) {
                    $('#scheduleSlots').load(reload + ' #slot', function () {
                        initRemove();
                        cleanDateTimePicker(document.getElementById('schedulePickr'))
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
    $('.scheduleSelect').click(function (e) {
        e.preventDefault();
        var $schedule = $(this).data('schedule');
        var $user = $(this).data('uid');
        var $type = $(this).data('type');
        var $url = $(this).data('url');
        var eleClicked = this;
        $.post($url, {user: $user, type: $type, time: $schedule}, function (data) {
            snackbar(data.text,data.color);
            var allEle = eleClicked.closest('.row').querySelectorAll('.scheduleSelect');
            allEle.forEach(function (ele) {
                var typ = ele.dataset.type;
                if (typ == 0){
                    ele.classList.remove('btn-success');
                    ele.classList.add('btn-outline-success')
                    if (eleClicked === ele){
                        ele.classList.add('btn-success');
                        ele.classList.remove('btn-outline-success')
                    }
                }else if (typ == 1){
                    ele.classList.remove('btn-danger');
                    ele.classList.add('btn-outline-danger')
                    if (eleClicked === ele){
                        ele.classList.add('btn-danger');
                        ele.classList.remove('btn-outline-danger')
                    }
                }else if (typ == 2){
                    ele.classList.remove('btn-warning');
                    ele.classList.add('btn-outline-warning')
                    if (eleClicked === ele){
                        ele.classList.add('btn-warning');
                        ele.classList.remove('btn-outline-warning')
                    }
                }

            })
        })
    })
}

function snackbar($text,$color) {
    $('#snackbar').text($text).removeClass('bg-danger').removeClass('bg-success').addClass('show').addClass('bg-'+$color).removeClass('d-none');
    setTimeout(function () {
        $('#snackbar').removeClass('show').addClass('d-none');
    }, 3000);

}

export {initScheduling, initSchedulePublic};
