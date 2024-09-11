import {enterMeeting, initWebsocket, leaveMeeting} from "./websocket";
import {close, inIframe, initModeratorIframe, showPlayPause, stopclosingMe} from "./moderatorIframe";
import {initStarSend} from "./endModal";
import {initStartWhiteboard} from "./startWhiteboard";
import * as mdb from 'mdb-ui-kit'; // lib
import ClipboardJS from 'clipboard'
import {initStartIframe} from "./createConference";
import {jitsiController} from "./pauseJitsi";
import {jitsiErrorHandling} from "./jitsiErrorHandling";
import {checkFirefox} from "./checkFirefox";
import {ConferenceUtils} from "./ConferenceUtils";
import {livekitApi} from "./livekit/main";
import {initSocialIcons} from "./createSocialButtons";
import {LivekitUtils} from "./livekit/livekitUtils";

var frameId;
var joined = false;
var pauseController;
var jitsiErrorController;
var avatarUrl = null;
var displayName = null;
var myId = null;
var roomName = null;
var isBreakout = null;


// Beispiel für die Nutzung der Klasse
const parentElementId = "jitsiWindow";  // ID des Elternelements

const api = new LivekitUtils(parentElementId, livekitUrl);



// api.addListener('chatUpdated', function (e) {
//     if (e.isOpen == true) {
//         document.querySelector('#logo_image').classList.add('transparent');
//     } else {
//         document.querySelector('#logo_image').classList.remove('transparent');
//     }
//
// });
//
// if (typeof options.userInfo.avatarUrl !== 'undefined'){
//     avatarUrl = options.userInfo.avatarUrl;
// }
// if (typeof options.userInfo.displayName !== 'undefined'){
//     displayName = options.userInfo.displayName;
// }
//
//
// api.addListener('videoConferenceJoined', function (e) {
//     enterMeeting();
//     initStartWhiteboard();
//     showPlayPause();
//     joined = true;
//     myId = e.id;
//     roomName = e.roomName;
//     isBreakout = e.breakoutRoom;
//     pauseController = new jitsiController(api,displayName,avatarUrl,myId, roomName,isBreakout);
//     jitsiErrorController= new jitsiErrorHandling(api);
//     conferenceUtils.initConferencePostJoin();
//
//
//     window.onbeforeunload = function (e) {
//         e.preventDefault();
//         e.stopImmediatePropagation();
//         return closeTabText;
//     }
//     api.addListener('readyToClose', function (e) {
//         leaveMeeting();
//         initStarSend();
//         api = null;
//     });
//
//     if (setTileview === 1) {
//         api.executeCommand('setTileView', {enabled: true});
//     }
//     if (avatarUrl !== '') {
//         api.executeCommand('avatarUrl', avatarUrl);
//     }
//     if (setParticipantsPane === 1) {
//         api.executeCommand('toggleParticipantsPane', {enabled: true});
//     }
// })
//

function docReady(fn) {
    // see if DOM is already available
    if (document.readyState === "complete" || document.readyState === "interactive") {
        // call on next available tick
        setTimeout(fn, 1);
    } else {
        document.addEventListener("DOMContentLoaded", fn);
    }
}


function checkClose() {
    if (!api.conferenceRunning) {
        close();
    }else {
      api.hangup();
    }

}

docReady(function () {
    var clipboard = new ClipboardJS('.copyLink');
    initModeratorIframe(checkClose);
    initWebsocket(websocketTopics);
});
