const startEgress = document.querySelectorAll('.startEgress');
const stopEgress = document.getElementById('stopEgress');
let egressID = null;
// Click-Events für alle Elemente mit der Klasse 'startEgress'
export function setupStartEgress() {
    const startRecordingButtons = document.querySelectorAll('.startEgress');
    const stopEgressElement = document.getElementById('stopEgress');
    const startEgressWrapper = document.getElementById('startEgress');

    if (startRecordingButtons && stopEgressElement && startEgressWrapper) {
        startRecordingButtons.forEach(button => {
            button.addEventListener('click', async (event) => {
                event.preventDefault();

                // URL für den Start-Request abrufen
                const url = button.getAttribute('href');

                try {
                    // HTTP-Request senden
                    const response = await fetch(url, { method: 'GET' });
                    const data = await response.json();

                    // Prüfen, ob error = false
                    if (data.error === false && data.recordingId) {
                        console.log('Aufnahme erfolgreich gestartet:', data);

                        // Egress-ID im Stop-Element setzen
                        stopEgressElement.setAttribute('data-egeressid', data.recordingId);
                        stopEgressElement.setAttribute('data-url', `/room/stop/egress/egressId`);

                        // Stop-Element sichtbar machen
                        stopEgressElement.closest('.wrapper').classList.remove('d-none');

                        // Start-Element unsichtbar machen
                        startEgressWrapper.classList.add('d-none');
                    } else {
                        console.error('Fehler beim Starten der Aufnahme:', data);
                    }
                } catch (error) {
                    console.error('Netzwerkfehler oder ungültige Response:', error);
                }
            });
        });
    }
}

// Funktion zum Stoppen der Aufnahme
export function setupStopEgress() {
    const stopEgressElement = document.getElementById('stopEgress');
    const startEgressWrapper = document.getElementById('startEgress');

    if (stopEgressElement && startEgressWrapper) {
        stopEgressElement.addEventListener('click', async () => {
            // URL für den Stop-Request abrufen
            const url = stopEgressElement.getAttribute('data-url').replace('egressId', stopEgressElement.getAttribute('data-egeressId'));

            if (url) {
                try {
                    // HTTP-Request senden
                    const response = await fetch(url, { method: 'GET' });
                    const data = await response.json();

                    // Prüfen, ob error = false
                    if (data.error === false) {
                        console.log('Aufnahme erfolgreich gestoppt:', data);

                        // Stop-Element unsichtbar machen
                        stopEgressElement.closest('.wrapper').classList.add('d-none');

                        // Start-Element sichtbar machen
                        startEgressWrapper.classList.remove('d-none');
                    } else {
                        console.error('Fehler beim Stoppen der Aufnahme:', data);
                    }
                } catch (error) {
                    console.error('Netzwerkfehler oder ungültige Response:', error);
                }
            } else {
                console.error('Keine gültige URL zum Stoppen vorhanden.');
            }
        });
    }
}
