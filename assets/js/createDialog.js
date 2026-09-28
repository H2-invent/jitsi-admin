import Swal from 'sweetalert2';
import {stopCallerRingtone} from './callerSound';

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
        }
    });
}

