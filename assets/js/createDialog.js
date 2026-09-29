import Swal from 'sweetalert2';
import {stopCallerRingtone} from './callerSound';

// Deadline for the ringing ad-hoc call. Cleared as soon as the dialog closes (answered, declined
// or Esc) so a late timer cannot report a missed call after the callee picked up.
let adhocTimeoutId = null;

function clearAdhocTimeout() {
    if (adhocTimeoutId) {
        clearTimeout(adhocTimeoutId);
        adhocTimeoutId = null;
    }
}

export function showDialog(data) {
    if (data.type !== 'dialog') return;

    const buttonsHtml = data.buttons.map((button, index) => {
        const dataAttributes = button.data ? Object.entries(button.data).map(([key, value]) => `data-${key}='${value}'`).join(' ') : '';
        return `
            <a id="swal-btn-${index}" class="${button.class || 'btn btn-primary'}" href="${button.link || '#'}" ${dataAttributes}>${button.text}</a>
        `;
    }).join(' ');

    Swal.fire({
        title: data.header,
        backdrop: false,
        html: `<p>${data.text}</p>${buttonsHtml}`,
        icon: data.dialogType,
        showConfirmButton: false,
        heightAuto: false,
        // Answering, declining, pressing Esc or clicking the backdrop all end the ring; stop the
        // caller ringtone whenever this (ringing) dialog closes.
        willClose: () => {
            stopCallerRingtone();
            clearAdhocTimeout();
        },
        didRender: () => {
            data.buttons.forEach((button, index) => {
                const element = document.getElementById(`swal-btn-${index}`);
                element.addEventListener('click', (event) => {
                    // Stop the ring as soon as a button is pressed; willClose covers Esc/backdrop.
                    stopCallerRingtone();
                    // Buttons that carry a server action must notify the backend first. The
                    // ad-hoc decline button tells the caller that the callee refused, so it
                    // cannot be reduced to just closing the dialog.
                    if (element.dataset.action && element.getAttribute('href') && element.getAttribute('href') !== '#') {
                        event.preventDefault();
                        fetch(element.getAttribute('href')).catch(() => {
                        }).finally(() => Swal.close());
                        return;
                    }
                    Swal.close();
                });
            });

            // Ringing ad-hoc calls carry the configured signaling duration (ADHOC_CALL_SIGNALING_DURATION).
            // When it elapses without an answer the backend is told so, which notifies the caller
            // and stops the ring on every device - independent of the messenger worker.
            clearAdhocTimeout();
            if (data.timeout && data.timeoutUrl) {
                adhocTimeoutId = setTimeout(() => {
                    adhocTimeoutId = null;
                    stopCallerRingtone();
                    fetch(data.timeoutUrl).catch(() => {
                    }).finally(() => Swal.close());
                }, data.timeout * 1000);
            }
        }
    });
}

