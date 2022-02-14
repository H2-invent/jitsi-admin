import $ from "jquery";
import autosize from "autosize";


function initDarkmodeSwitch() {
    $('.switchDarkmode').change(function (e) {
        var val = 0;
        if ($(this).prop('checked')) {
            val = 1
        }
        setCookie('DARK_MODE', val, 365);
        window.location.reload();
    })
}
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

export {initDarkmodeSwitch};
