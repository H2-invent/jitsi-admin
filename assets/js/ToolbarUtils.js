import {initSocialIcons} from "./createSocialButtons";

class ToolbarUtils {

    chatBtn = document.getElementById('externalChat');
    frame = document.getElementById('frame');
    sidebar = document.getElementById('wrapperIcons');
    sidebarTimeout = null;
    floatingTag = document.getElementById('tagContent')
    content = document.getElementById('inviteButtonOpenRoomContent');
    inviteBtn = document.getElementById('inviteButtonOpenRoom');
    closeBtn = document.getElementById('inviteButtonOpenRoomClose');
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

            if (floatingTag) {
                floatingTag.classList.remove('show')
            }

        }, 3000);
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

        if (this.inviteBtn) {
            this.inviteBtn.addEventListener('click',()=>{ this.toggleInvitationPane()})
            this.closeBtn.addEventListener('click', ()=>{this.hideInvitePane()})
            this.showInvitePane();
        }
    }
    toggleInvitationPane(){
        if (this.content.classList.contains('show')){
            this.content.classList.remove('show');
        }else {
            this.content.classList.add('show');
        }
    }
    showInvitePane(){
        this.content.classList.add('show');
    }
    hideInvitePane(){
        this.content.classList.remove('show');
    }

}

export {ToolbarUtils}

