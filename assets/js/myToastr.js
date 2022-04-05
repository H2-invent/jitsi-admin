
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


function setSnackbar(text, color) {
    if(color == 'danger'){
        color = 'error';
    }
    Toastr[color](text)
    // $('#snackbar').text(text).removeClass('bg-danger').removeClass('bg-warning').removeClass('bg-success').removeClass('d-none').addClass('show bg-' + color).click(function (e) {
    //     $(this).removeClass('show');
    // });
    // $('#snackbar').mouseleave(function (e) {
    //     $(this).removeClass('show');
    // })
}


export {setSnackbar}
