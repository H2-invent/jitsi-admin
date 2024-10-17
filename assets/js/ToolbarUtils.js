import {initSocialIcons} from "./createSocialButtons";

class ToolbarUtils {


    chatBtn = document.getElementById('externalChat');
    frame = document.getElementById('frame');
    sidebar = document.getElementById('wrapperIcons');
    sidebarTimeout = null;
    floatingTag = document.getElementById('tagContent')


    constructor() {
        this.sidebar.addEventListener('mouseover', () => {
            clearTimeout(this.sidebarTimeout);
        });
        this.inviteParticipantsToggle();
    }

    sidebarAction() {
        var sidebar = this.sidebar

        var floatingTag = this.floatingTag;
        clearTimeout(this.sidebarTimeout);
        this.sidebarTimeout = setTimeout(function () {
            sidebar.classList.remove('show');
            if (floatingTag) {
                floatingTag.classList.remove('show')
            }

        }, 3000);
        sidebar.classList.add('show');
        if (floatingTag) {
            floatingTag.classList.add('show')
        }
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



    inviteParticipantsToggle() {
        var inviteBtn = document.getElementById('inviteButtonOpenRoom');
        if (inviteBtn) {
            var closeBtn = document.getElementById('inviteButtonOpenRoomClose');
            inviteBtn.addEventListener('click', this.toggleInviteContent)
            closeBtn.addEventListener('click', this.toggleInviteContent)
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


}

export {ToolbarUtils}

