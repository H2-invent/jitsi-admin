import Swal from 'sweetalert2';

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
        didRender: () => {
            data.buttons.forEach((button, index) => {
                document.getElementById(`swal-btn-${index}`).addEventListener('click', () => {
                    Swal.close();
                });
            });
        }
    });
}

