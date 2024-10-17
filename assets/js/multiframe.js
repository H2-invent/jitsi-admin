import md5 from "blueimp-md5";
import $ from "jquery";
import {tooltip} from 'mdb-ui-kit';
import {setCookie} from "./cookie";
import {checkIfIsMutable, zIndex} from "./createConference";

export class multiframe {


    messages = [];
    messageTimeout = [];
    isMutable = false;
    isMaximized = false;
    inMinibar = false;
    isPaused = false;
    random = null;
    eventListeners = {};

    constructor(
        url,
        title,
        startMaximized = true,
        borderColor = '',
        xStarting,
        yStarting,
        height,
        width,
        zIndex,
    ) {
        this.url = url;
        this.title = title;
        this.borderColor = borderColor;
        this.xValue = xStarting;
        this.yValue = yStarting;
        this.height = height;
        this.width = width;
        this.zIndex = zIndex;
        this.createIframe();
        if (startMaximized) {
            this.prepareMaximize();
            this.maximizeWindow();
        }
    }

    createIframe() {
        if (window.$chatwoot) {
            window.$chatwoot.toggleBubbleVisibility("hide"); // to hide the bubble
        }
        let urlPath = this.url.split('?')[0];
        this.random = md5(urlPath);
        var html =
            `<div id="jitsiadminiframe${this.random}" class="jitsiadminiframe" data-x="${this.xValue}" data-y="${this.yValue}" data-maximal="0" style="border-color: ${this.borderColor}">
            <div class="headerBar">
            <div class="dragger"><i class="fa-solid fa-arrows-up-down-left-right me-2"></i>${this.title}</div>
            <div class="actionIconLeft">
            <div class="pauseConference d-none  actionIcon" data-pause="0"><i class="fa-solid fa-pause" data-mdb-toggle="tooltip" title="Pause"></i></div> 
            <div class="minimize  actionIcon"><i class="fa-solid fa-window-minimize" data-mdb-toggle="tooltip" title="Minimize"></i></div> 
            <div class=" button-restore actionIcon" data-maximal="0" data-mdb-toggle="tooltip" title="Restore"><i class="fa-solid fa-window-restore"></i></div> 
            <div class=" button-maximize  actionIcon" data-maximal="0" data-mdb-toggle="tooltip" title="Maximize"><i class="fa-solid fa-window-maximize"></i></div> 
            ${document.fullscreenEnabled ? '<div class="button-fullscreen actionIcon" data-mdb-toggle="tooltip" title="Fullscreen"><i class="fa-solid fa-expand"></i></div> ' : ''}
            <div class="closer  actionIcon"><i class="fa-solid fa-xmark" data-mdb-toggle="tooltip" title="Exit"></i></div>
            </div>
            </div>
            <div class="iframeFrame">
            <iframe  class="multiframeIframe"></iframe>
            </div>
            </div>`;
        if (document.getElementById('window')) {
            document.getElementById('window').insertAdjacentHTML('beforeend', html);
        } else {
            document.querySelector('body').insertAdjacentHTML('beforeend', html);
        }
        $('[data-mdb-toggle="tooltip"]').tooltip();
        this.frame = document.getElementById('jitsiadminiframe' + this.random);
        this.frame.style.transform = `translate(${this.xValue}px, ${this.yValue}px)`;
        this.frame.style.width = this.width + 'px';
        this.frame.style.height = this.height + 'px';
        this.frame.style.zIndex = this.zIndex;
        this.iframe = this.frame.querySelector('iframe');
        this.iframe.src = this.url;
        window.addEventListener("message", this.recievecommand.bind(this));
        this.addEventlistener();
        return this;
    };

    addEventlistener() {
        this.frame.addEventListener('dblclick', (e) => {
            this.toggleMaximize();
        })
        this.frame.querySelector('.closer').addEventListener('click', (e) => {
            e.stopPropagation();
            this.closeFrame();

        })

        this.frame.querySelector('.minimize').addEventListener('click', (e) => {
            e.stopPropagation();
            this.minimizeFrame()
            this.triggerRemoveinteraction()
        })

        this.frame.querySelector('.pauseConference').addEventListener('click', (e) => {
            e.stopPropagation();
            this.pauseIframe();
        })


        this.frame.querySelector('.button-fullscreen').addEventListener('click', (e) => {
            e.stopPropagation();
            this.fulscreenWindow();
        })

        this.frame.querySelector('.button-maximize').addEventListener('click', (e) => {
            e.stopPropagation();
            this.triggerRemoveinteraction();
            this.prepareMaximize();
            this.maximizeWindow();
        })

        this.frame.querySelector('.button-restore').addEventListener('click', (e) => {
            this.restoreWindowFromMaximized();
            this.triggerRemoveinteraction()
        });

        this.frame.addEventListener('click', (e) => {
            this.moveInForeground(event.currentTarget);
        })
    }

    isFullscreen() {
        var st = screen.top || screen.availTop || window.screenTop;
        if (st != window.screenY) {
            return false;
        }
        return window.fullScreen == true || screen.height - document.documentElement.clientHeight <= 30;
    }

    recievecommand(event) {
        // Sicherstellen, dass die Nachricht aus dem erwarteten iframe kommt
        if (event.source === this.iframe.contentWindow) {
            // Beispiel: Ausgabe der Daten an die Konsole
            console.log("Nachricht vom iframe empfangen:", event.data);
        } else {
            return;
        }
        let decoded;
        try {
            decoded = JSON.parse(event.data);
        } catch (e) {
            return false;
        }

        const type = decoded.type

        if (type === 'closeMe') {
            clearTimeout(this.closingTimeout);
            delete this.closingTimeout;
            this.purgeFrame()

        } else if (type === 'stopClosingMe') {
            clearTimeout(this.closingTimeout);
            delete this.closingTimeout;
        } else if (type === 'openNewIframe') {
            //todo in controllerklasse
            this.triggerCreateNewMultiframe(decoded.url, decoded.title, false);
        } else if (type === 'showPlayPause') {
            this.frame.classList.add('isMutable');
            this.frame.dataset.muted = "0";
            this.frame.querySelector('.pauseConference').classList.remove('d-none');
            this.isMutable = true;
        } else if (type === 'colorBorder') {
            if (multiframe) {
                this.frame.style.borderColor = decoded.color;
            }

        } else if (type === 'ack') {
            var messageId = decoded.messageId
            clearTimeout(this.messageTimeout[messageId]);
            delete this.messageTimeout[messageId];
        }
    }

    closeFrame() {
        this.sendCommand({type: 'pleaseClose'})
        this.closingTimeout = setTimeout(() => {
            this.purgeFrame();
        }, 100);
    }

    sendCommand(message) {

        var messageId = this.makeid(32);
        message.frameId = "00";
        message.scope = 'jitsi-admin-iframe';
        message.messageId = messageId;
        this.iframe.contentWindow.postMessage(message, '*');
        this.messageTimeout[messageId] = setTimeout(() => {
            this.purgeFrame();
        }, 1000)
    }

    makeid(length) {
        var result = '';
        var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
        var charactersLength = characters.length;
        for (var i = 0; i < length; i++) {
            result += characters.charAt(Math.floor(Math.random() *
                charactersLength));
        }
        return result;
    }

    purgeFrame() {

        this.frame.remove();
        this.triggerRemoveEvent();
        $('.tooltip').remove();
        if (window.$chatwoot) {
            var $iframes = document.querySelectorAll('.jitsiadminiframe');

            if ($iframes.length == 0) {
                window.$chatwoot.toggleBubbleVisibility("show"); // to hide the bubble
            }

        }

    }

    toggleMaximize() {
        if (this.isMaximized) {
            this.restoreWindowFromMaximized()
        } else {
            this.prepareMaximize()
            this.maximizeWindow()
        }
    }

    restoreWindowFromMaximized() {
        setCookie('startMaximized', 0, 365)

        var maxiIcon = this.frame.querySelector('.button-maximize');
        var restoreButton = this.frame.querySelector('.button-restore');
        if (this.isMaximized) {
            this.frame.style.width = this.widthbeforeMaximize + 'px';
            this.frame.style.height = this.heightbeforeMaximize + 'px';
            this.frame.style.transform = this.transformbeforeMaximize;
            this.frame.style.removeProperty('border-width');
            this.frame.dataset.x = this.xbeforeMaximize;
            this.frame.dataset.y = this.ybeforeMaximize;
            this.frame.dataset.maximal = "0";
            maxiIcon.classList.remove('d-none');
            restoreButton.classList.add('d-none');
            this.frame.classList.remove('maximized');
            this.isMaximized = false;
        }
    }

    prepareMaximize() {
        this.xbeforeMaximize = this.frame.dataset.x;
        this.ybeforeMaximize = this.frame.dataset.y;
        this.heightbeforeMaximize = this.frame.offsetHeight;
        this.widthbeforeMaximize = this.frame.offsetWidth;
        this.transformbeforeMaximize = this.frame.style.transform;

    }

    maximizeWindow(container) {
        setCookie('startMaximized', 1, 365)

        var maxiIcon = this.frame.querySelector('.button-maximize');
        var restoreButton = this.frame.querySelector('.button-restore');
        if (!this.isMaximized) {
            this.frame.style.width = "100%";
            this.frame.style.height = "100%";
            this.frame.style.transform = 'translate(0px, 0px)'
            this.frame.style.borderWidth = '0px'
            this.frame.classList.add('maximized');

            restoreButton.classList.remove('d-none');
            maxiIcon.classList.add('d-none');
            this.frame.dataset.maximal = "1";
            this.isMaximized = true;
        }
    }

    minimizeFrame() {
        this.moveToMinibar();
    }

    moveToMinibar() {

        if (this.inMinibar) {
            return null;
        }
        this.frame.insertAdjacentHTML('afterbegin', '<div class="minimizeOverlay" style="position: absolute; z-index: 2; height: 100%; width: 100%; opacity: 0.0; background-color: inherit; cursor: pointer"></div>');
        this.iframe.style.height = '0px';
        this.widthBeforeMinimize = this.frame.style.width;
        this.frame.classList.add('minified');
        this.frame.querySelector('.minimizeOverlay').addEventListener('click', this.removeFromMinibar.bind(this));


        this.setWidthOfminified();
        this.iframe.style.removeProperty('height');
        this.inMinibar = true;
    }

    setWidthOfminified() {
        var ele = document.querySelectorAll('.minified');
        var leftCounter = 0
        for (var e of ele) {
            e.style.width = window.innerWidth / ele.length + 'px';
            e.style.left = leftCounter + 'px';
            leftCounter += window.innerWidth / ele.length;
        }
    }

    removeFromMinibar(e) {

        e.currentTarget.removeEventListener('click', this.removeFromMinibar.bind(this));
        this.restoreMinimized();
        e.currentTarget.remove();
    }

    restoreMinimized() {
        if (this.inMinibar) {
            this.frame.classList.remove('minified');
            this.frame.style.width = this.widthBeforeMinimize;
            this.frame.style.removeProperty('left');
            this.setWidthOfminified();
            this.inMinibar = false;
            this.triggerAddinteraction();

        }

    }

    pauseIframe() {
        if (!this.isPaused) {
            this.pauseFrame();
        } else {
            this.playFrame();
        }
    }

    pauseFrame() {
        if(!this.isPaused){
            this.isPaused = true;
            this.frame.querySelector('.pauseConference').innerHTML = '<i class="fa-solid fa-play"></i>';
            this.frame.querySelector('.iframeFrame').insertAdjacentHTML('afterbegin', '<div class="pausedFrame"><i class="fa-solid fa-circle-pause"></i></div>');
            this.sendCommand({type: 'pauseIframe'});
        }

    }

    playFrame() {
        if (this.isPaused){
            this.isPaused = false;
            this.frame.querySelector('.pauseConference').innerHTML = '<i class="fa-solid fa-pause"></i>';
            this.frame.querySelector('.pausedFrame').remove();
            this.sendCommand({type: 'playIframe'})
        }

    }

    fulscreenWindow() {
        this.iframe.requestFullscreen();
    }

    moveInForeground() {
        checkIfIsMutable(this);
    }

    setZindex(zIndex) {
        this.zIndex = zIndex;
        this.frame.style.zIndex = zIndex;
    }

    addEventListener(event, callback) {
        if (!this.eventListeners[event]) {
            this.eventListeners[event] = [];
        }
        this.eventListeners[event].push(callback);
    }

    // Methode zum Entfernen von Event-Listenern
    removeEventListener(event, callback) {
        if (!this.eventListeners[event]) return;
        this.eventListeners[event] = this.eventListeners[event].filter(listener => listener !== callback);
    }

    // Methode zum Triggern von Events mit Daten
    triggerEvent(event, data = {}) {
        if (!this.eventListeners[event]) return;
        const eventObject = {type: event, ...data};
        this.eventListeners[event].forEach(callback => callback(eventObject));
    }


    // Methode zum Auslösen des "remove"-Events
    triggerRemoveEvent() {
        this.triggerEvent('remove')
    }


    triggerAddinteraction() {
        this.triggerEvent('addInteraction')

    }

    triggerRemoveinteraction() {
        this.triggerEvent('removeInteraction')

    }

    triggerCreateNewMultiframe(url, title, maximize) {
        this.triggerEvent('createNewMultiframe', {url: url, title: title, maximize: maximize})

    }
}