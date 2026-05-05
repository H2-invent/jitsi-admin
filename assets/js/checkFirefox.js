import { Modal, initMDB } from "mdb-ui-kit";
import ClipboardJS from 'clipboard'
export function checkFirefox() {
    var clipboard = new ClipboardJS('.copyLink');
    var noFirefoxModal = document.getElementById('noFIrefoxModal');

    if (noFirefoxModal) {
        var url = window.location.href;


        var edge = noFirefoxModal.querySelector('.btn-edge');
        if (edge) {
            edge.href = 'microsoft-edge:' + url;
        }
        var chrome = noFirefoxModal.querySelector('.btn-chrome');
        if (chrome) {
            chrome.href = url.replace('http','googlechrome');
        }
        var btnSafari = noFirefoxModal.querySelector('.btn-safari');
        if (btnSafari) {
            btnSafari.href = 'safari:' + url;
        }

        const modal = new Modal(noFirefoxModal, options)

        modal.show();
    }
}