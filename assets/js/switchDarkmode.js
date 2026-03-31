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

    $(document).on('click', '.toggleDarkmode', function (e) {
        e.preventDefault();
        var currentVal = getCookie('DARK_MODE');
        var val = (currentVal === '1') ? 0 : 1;
        setCookie('DARK_MODE', val, 365);
        window.location.reload();
    })
}

function getCookie(cname) {
    var name = cname + "=";
    var decodedCookie = decodeURIComponent(document.cookie);
    var ca = decodedCookie.split(';');
    for (var i = 0; i < ca.length; i++) {
        var c = ca[i].trim();
        if (c.indexOf(name) === 0) {
            return c.substring(name.length, c.length);
        }
    }
    return "";
}
function setCookie(cname, cvalue, exdays) {
    var d = new Date();
    d.setTime(d.getTime() + (exdays * 24 * 60 * 60 * 1000));
    var expires = "expires=" + d.toUTCString();
    document.cookie = cname + "=" + cvalue + ";" + expires + ";path=/";
}

export {initDarkmodeSwitch};
