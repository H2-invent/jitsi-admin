import * as Toastr from "toastr";

Toastr.options = {
    "closeButton": true,
    "debug": false,
    "newestOnTop": true,
    "progressBar": true,
    "positionClass": "toast-top-right",
    "preventDuplicates": true,
    "onclick": null,
    "showDuration": "300",
    "hideDuration": "1000",
    "timeOut": "0",
    "extendedTimeOut": "200",
    "showEasing": "swing",
    "hideEasing": "linear",
    "showMethod": "fadeIn",
    "hideMethod": "fadeOut"
}


function setSnackbar(text, color, closeWithHover = false, id = '0x00',timeout=0) {
    if (color === 'danger') {
        color = 'error';
    }
    Toastr.options.timeOut = timeout;
    if (closeWithHover === true) {
        Toastr.options.timeOut = 0;
        Toastr.options.extendedTimeOut = 0;
    } else {
        Toastr.options.extendedTimeOut = 200;
    }
    text = '<span id="jitsi_toastr_' + id + '"></span>' + text;
    Toastr[color](text)

}

function deleteToast(id) {
    var tid = '#jitsi_toastr_' + id;
    if (document.querySelector(tid)) {
        var ele = document.querySelector(tid).closest('.toast');
        ele.remove();
    }


}


export {setSnackbar, deleteToast}
