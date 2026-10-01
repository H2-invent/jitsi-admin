export class CallerTag {
    emptyPrependText = ' - ';
    text = 'Telefonteilnehmer anwesend';
    textColor = '#000'; // black
    backgroundColor = '#ff5959'; // pastel red

    previousText = '';
    previousTextColor = '';
    previousBackgroundColor = '';

    tagContentElement;
    amountOfPhoneCallers;

    constructor(
        amountOfPhoneCallers
    ) {
        this.tagContentElement = document.getElementById('tagContent');
        this.amountOfPhoneCallers = amountOfPhoneCallers;
        if (amountOfPhoneCallers > 0) {
            this.addCallerTag();
        }
    }

    updateAmountOfPhoneCallers(newAmount) {
        if (newAmount === this.amountOfPhoneCallers) {
            return;
        }
        if (this.firstPhoneCallerJoined(newAmount)) {
            this.addCallerTag();
        }
        if (this.lastPhoneCallerLeft(newAmount)) {
            this.removeCallerTag();
        }
        this.amountOfPhoneCallers = newAmount;
    }

    firstPhoneCallerJoined(newAmount) {
        return this.amountOfPhoneCallers === 0 && newAmount > 0;
    }

    lastPhoneCallerLeft(newAmount) {
        return this.amountOfPhoneCallers > 0 && newAmount === 0;
    }

    addCallerTag() {
        let textToAdd = this.text;
        if (this.hasExistingTag()) {
            textToAdd = this.emptyPrependText + textToAdd;
            this.previousText = this.tagContentElement.textContent;
            this.previousTextColor = this.tagContentElement.style.color;
            this.previousBackgroundColor = this.tagContentElement.style.backgroundColor;
        }
        this.tagContentElement.textContent += textToAdd;
        this.tagContentElement.style.color = this.textColor;
        this.tagContentElement.style.backgroundColor = this.backgroundColor;

        this.tagContentElement.classList.remove('d-none');
        this.updateFrame(this.backgroundColor)
    }

    removeCallerTag() {
        this.tagContentElement.textContent = this.previousText;
        this.tagContentElement.style.color = this.previousTextColor;
        this.tagContentElement.style.backgroundColor = this.previousBackgroundColor;
        this.updateFrame(this.previousBackgroundColor);
    }

    updateFrame(color) {
        const message = JSON.stringify({
            scope: 'jitsi-admin-iframe',
            type: 'updateTag',
            color: color,
        });
        window.parent.postMessage(message, '*');
    }

    hasExistingTag() {
        return !this.tagContentElement.classList.contains('d-none');
    }
}

