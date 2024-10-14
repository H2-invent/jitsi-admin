import {initSocialIcons} from "./createSocialButtons";
import {ToolbarUtils} from "./ToolbarUtils";

class ConferenceUtils {

    api = null;
    chatBtn = document.getElementById('externalChat');
    frame = document.getElementById('frame');
    sidebar = document.getElementById('wrapperIcons');
    sidebarTimeout = null;
    floatingTag = document.getElementById('tagContent');

    constructor(api) {
        this.api = api;
        this.toolbar = new ToolbarUtils();

    }

    initConferencePreJoin() {
        this.initSidebarMove();
    }

    initSidebarMove() {

        this.api.addEventListener("mouseMove", (event) => {
            this.toolbar.sidebarAction();
        });
        this.api.addEventListener("mouseEnter", (event) => {
            this.toolbar.sidebarAction();
        });
    }


    initConferencePostJoin() {
        if (typeof disableFilmstrip !== 'undefined') {
            if (disableFilmstrip) {
                this.toggleFilmstrip();
            }
        }
        this.setE2EDefault();
        initSocialIcons(this.changeCamera.bind(this));
        this.initChatToggle();
        this.initToggleFilmstripe();
    }

    changeCamera(cameraId) {
        this.api.setVideoInputDevice(cameraId);
    }

    toggleFilmstrip() {
        this.api.executeCommand('toggleFilmStrip');
    }

    setE2EDefault() {
        if (typeof enforceE2Eencryption !== 'undefined') {
            if (enforceE2Eencryption) {
                this.switchE2EOn();
            } else {
                this.switchE2EOff();
            }
        }
    }

    switchE2EOn() {
        this.api.executeCommand('toggleE2EE', true);
    }

    switchE2EOff() {
        this.api.executeCommand('toggleE2EE', false);
    }


    removeEtherpad() {

        var etherpad = document.querySelector('.startEtherpad');
        if (etherpad) {
            etherpad.closest('.wrapper').remove();
        }
    }

    removeWhiteboard() {
        var whiteboard = document.querySelector('.startWhiteboard');
        if (whiteboard) {
            whiteboard.closest('.wrapper').remove();
        }
    }

    initChatToggle() {
        var api = this.api;
        this.chatBtn = document.getElementById('externalChat');
        if (!this.chatBtn) {
            return false;
        }
        var filterDot = this.chatBtn.querySelector('.filter-dot');
        var chatBtn = this.chatBtn
        if (this.chatBtn) {
            this.chatBtn.addEventListener('click', function () {
                api.executeCommand('toggleChat');
            })
            api.addListener('chatUpdated', function (data) {
                console.log(data);
                if (data.unreadCount > 0) {
                    filterDot.classList.remove('d-none');
                    filterDot.textContent = data.unreadCount;
                    chatBtn.style.setProperty('background-color', '#2561ef', 'important');
                    chatBtn.style.color = '#ffffff';
                } else {
                    filterDot.classList.add('d-none');
                    chatBtn.style.removeProperty('background-color');
                    chatBtn.style.removeProperty('color');
                }
            });
        }
    }



    toggleInviteContent(ele) {
        var content = document.getElementById('inviteButtonOpenRoomContent');
        if (content.classList.contains('show')) {
            content.classList.remove('show');
        } else {
            content.classList.add('show');
        }
    }

    initToggleFilmstripe() {
        var content = document.getElementById('toggleFilmstripe');
        var api = this.api
        content.addEventListener('click', function (e) {
            api.executeCommand('toggleFilmStrip');

        })
    }

}

export {ConferenceUtils}

