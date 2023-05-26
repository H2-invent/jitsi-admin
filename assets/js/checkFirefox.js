import * as mdb from "mdb-ui-kit";

export function checkFirefox() {
    var noFirefoxModal = document.getElementById('noFIrefoxModal');

    if (noFirefoxModal) {
        var url = window.location.href;


        var edge = noFirefoxModal.querySelector('.btn-edge');
        if (edge) {
            edge.href = 'microsoft-edge:' + url;
        }
        var chrome = noFirefoxModal.querySelector('.btn-chrome');
        if (chrome) {
            chrome.href = 'googlechrome:' + url;
        }
        var btnSafari = noFirefoxModal.querySelector('.btn-safari');
        if (btnSafari) {
            btnSafari.href = 'safari:' + url;
        }

        var modal = new mdb.Modal(noFirefoxModal);
        modal.show();
    }
}