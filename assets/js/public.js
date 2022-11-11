import * as mdb from 'mdb-ui-kit'; // lib
import ClipboardJS from 'clipboard'



function docReady(fn) {
    // see if DOM is already available
    if (document.readyState === "complete" || document.readyState === "interactive") {
        // call on next available tick
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}
docReady(function() {
    var clipboard = new ClipboardJS('.copyLink');
});
