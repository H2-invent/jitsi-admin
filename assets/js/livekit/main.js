export class livekitApi {
    constructor(parentElement, url) {
        this.parentElement = parentElement;
        this.url = url;

        // Erstellen des iframes
        this.iframe = document.createElement("iframe");

        // Die URL des iframes setzen
        this.iframe.src = this.url;

        // Optional: Größe des iframes setzen
        this.iframe.width = "100%";
        this.iframe.height = "100%";

        // Style für das iframe setzen, um es responsiv zu machen
        this.iframe.style.border = "none";
        this.iframe.style.width = "100%";
        this.iframe.style.height = "100%";

        this.iframe.allow = "autoplay; camera; clipboard-write; compute-pressure; display-capture; hid; microphone; screen-wake-lock; speaker-selection";

        // Das iframe in das parentElement einfügen
        document.getElementById(this.parentElement).appendChild(this.iframe);

        // Event listener für Nachrichten aus dem iframe
        window.addEventListener("message", this.handleMessage.bind(this));
    }

    handleMessage(event) {
        // Sicherstellen, dass die Nachricht aus dem erwarteten iframe kommt
        if (event.source === this.iframe.contentWindow) {
            // Beispiel: Ausgabe der Daten an die Konsole
            console.log("Nachricht vom iframe empfangen:", event.data);

            // Event auslösen und Daten weitergeben
            this.triggerEvent(event.name, event.data);
        }
    }

    triggerEvent(eventName, eventData) {
        const event = new CustomEvent(eventName, { detail: eventData });
        document.dispatchEvent(event);
    }

    addEventListener(eventName, callback) {
        document.addEventListener(eventName, callback);
    }
}