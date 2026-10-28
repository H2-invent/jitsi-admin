export class CallerTag {
    prependHTML = '&nbsp;-&nbsp;';
    innerHTML;
    textColor = '#000'; // black
    backgroundColor = '#ff5959'; // pastel red

    previousInnerHTML = '';
    previousTextColor = '';
    previousBackgroundColor = '';

    tagContentElement;
    amountOfPhoneCallers;

    constructor(
        amountOfPhoneCallers
    ) {
        this.tagContentElement = document.getElementById('tagContent');
        this.innerHTML = this.tagContentElement.dataset.callerTagHtml;
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
        let htmlToAdd = this.innerHTML;
        if (this.hasExistingTag()) {
            htmlToAdd = this.prependHTML + htmlToAdd;
            this.previousInnerHTML = this.tagContentElement.innerHTML;
            this.previousTextColor = this.tagContentElement.style.color;
            this.previousBackgroundColor = this.tagContentElement.style.backgroundColor;
        }
        this.tagContentElement.innerHTML += htmlToAdd;
        this.tagContentElement.style.color = this.textColor;
        this.tagContentElement.style.backgroundColor = this.backgroundColor;

        this.tagContentElement.classList.remove('d-none');
        this.updateFrame(this.backgroundColor)
    }

    removeCallerTag() {
        this.tagContentElement.innerHTML = this.previousInnerHTML;
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

