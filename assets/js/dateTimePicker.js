import duDatepicker from '@dmuy/datepicker'
import mdtimepicker from '@dmuy/timepicker'
import moment from 'moment'
function initdateTimePicker($element,
                            $options = {
                                altFormat: 'DD.MM.YYYY HH:mm',
                                dateFormat: 'YYYY-MM-DDTHH:mm',
                                enableTime: true,
                                time_24hr: true,
                                minDate: "today",
                            }) {

    var $name = $element.slice(1);
    var dateSave;
    var timeSave;
    if ($element.charAt(0) === '.') {
        var $ele = document.getElementsByClassName($name);
    } else if ($element.charAt(0) === '#') {
        var $ele = document.getElementById($name);
    }

    var $length = $ele.length
    for (var $i = 0; $i < $length; $i++) {
        $ele[$i].style.display = 'none';
        $ele[$i].classList.add('original');
        var $input = document.createElement("input");
        $input.type = "text";
        $input.readOnly=true;
        $input.style.display='block';
        for (var $c = 0; $c < $ele[$i].classList.length; $c++){
            $input.classList.add($ele[$i].classList[$c]);
        }
        $input.classList.add($name+'_alt');
        $ele[$i].parentNode.appendChild($input);
    }

//first we let the people select the date
    duDatepicker($element+'_alt', {
        i18n: 'de', format: 'dd.mm.yyyy', auto: true, minDate: 'today',
        events: {
            dateChanged(data, datepicker) {
                dateSave = moment(data._date).format('YYYY-MM-DD')
                mdtimepicker(datepicker.input,{
                    is24hour: true,
                    events:{
                        timeChanged(data, timepicker){
                            timeSave = data.time;
                            var $time = moment(dateSave+'T'+timeSave);
                            mdtimepicker(timepicker.input,'destroy');
                            timepicker.input.value = $time.format($options.altFormat)
                            timepicker.input.readOnly=true;
                            timepicker.input.parentNode.querySelector('.original').value =$time.format($options.dateFormat)
                        }
                    }
                })
                mdtimepicker(datepicker.input, 'setValue', moment().format('HH:mm'));
                mdtimepicker(datepicker.input,'show');
            }
        }
    });
}

export {initdateTimePicker}
