// Inits Click-Events and shows recording icons
export function initRecording() {
    setupStartEgress();
    setupStopEgress();
    setupTranscriptionToggle();
    document.getElementById('wrapper-recording').classList.remove('d-none');
}

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
                    if (data.error !== false || !data.recordingId) {
                        console.error('Fehler beim Starten der Aufnahme:', data);
                        return;
                    }

                    console.log('Aufnahme erfolgreich gestartet:', data);

                    // Egress-ID im Stop-Element setzen
                    const stopEgressUrl = stopEgressElement.getAttribute('href').replace('REPLACE', data.recordingId);
                    stopEgressElement.setAttribute('href', stopEgressUrl);

                    // Stop-Element sichtbar machen
                    stopEgressElement.closest('.wrapper').classList.remove('d-none');

                    // Start-Element unsichtbar machen
                    startEgressWrapper.classList.add('d-none');

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
        stopEgressElement.addEventListener('click', async (event) => {
            event.preventDefault();

            // URL für den Stop-Request abrufen
            const url = event.target.href;

            if (!url) {
                console.error('Keine gültige URL zum Stoppen vorhanden.');
                return;
            }

            try {
                // HTTP-Request senden
                const response = await fetch(url, { method: 'GET' });
                const data = await response.json();

                // Prüfen, ob error = false
                if (data.error) {
                    console.error('Fehler beim Stoppen der Aufnahme:', data);
                    return;
                }

                console.log('Aufnahme erfolgreich gestoppt:', data);

                // Stop-Element unsichtbar machen
                stopEgressElement.closest('.wrapper').classList.add('d-none');

                // Start-Element sichtbar machen
                startEgressWrapper.classList.remove('d-none');

            } catch (error) {
                console.error('Netzwerkfehler oder ungültige Response:', error);
            }
        });
    }
}

function setupTranscriptionToggle() {
    const transcriptionToggles = document.querySelectorAll('.toggleTranscription');
    transcriptionToggles.forEach(label => {
        const checkbox = label.querySelector('input[type="checkbox"]');
        const url = label.dataset.href;

        checkbox.addEventListener('change', async (e) => {
            const isChecked = e.target.checked;
            // keep both checkboxes in sync
            document.querySelectorAll('.toggleTranscription input[type="checkbox"]').forEach(checkbox => checkbox.checked = isChecked);
            try {
                const response = await fetch(url, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ enabled: isChecked })
                });
                const data = await response.json();

                if (!data.error) {
                    return;
                }

                console.error('Fehler beim Aktivierung der Transkription:', data);
                // reset checkbox on error
                document.querySelectorAll('.toggleTranscription input[type="checkbox"]').forEach(checkbox => checkbox.checked = !isChecked);

            } catch (error) {
                console.error('Netzwerkfehler oder ungültige Response:', error);
            }
        });
    });
}

