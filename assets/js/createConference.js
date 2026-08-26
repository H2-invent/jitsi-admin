import md5 from "blueimp-md5"
import {setSnackbar} from "./myToastr";
import interact from "interactjs";
import {getCookie} from './cookie'
import {multiframe} from "./multiframe";
import {sendViaWebsocket} from "./websocket";


let counter = 50;
let zIndexOffset = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;
let interaction;
let dragactive = false;
let multiframes = [];
let tryfullscreen = null;
let blockedUrls = [];

function initStartIframe() {

    document.addEventListener("mouseover", function (ele) {

        initInteractionFrame(ele);

    });
    document.addEventListener("click", function (ele) {
        initInteractionFrame(ele);
    });

    document.addEventListener('click', (e) => {
        if (e.target.closest('.startIframe')) {

            e.preventDefault();
            var target = e.target.closest('.startIframe')
            if ("iframetoast" in target.dataset) {
                setSnackbar(target.dataset.iframetoast, '', 'danger');
            } else {
                const isMaximized = getCookie('startMaximized') ? getCookie('startMaximized') : 1;
                createIframe(target.href, target.dataset.roomname, isMaximized == 1, true, target.dataset.bordercolor);
            }
        }
    });


    // addEventListener('resize', (event) => {
    //     setWidthOfminified();
    // });
}


function createIframe(url, title, startMaximized = true, borderColor = '', roomUid = null) {

    width = window.innerWidth * 0.75;
    height = window.innerHeight * 0.75;
    counter = (document.querySelectorAll('.jitsiadminiframe').length + 1) * 50;

    var urlPath = url.split('?')[0];
    var random = md5(urlPath);

    const existingMultiframe = multiframeCheck(random);
    const isInBlockedUrl = checkIfUrlIsBlocked(url);
    if (isInBlockedUrl) {
        return;
    }
    if (existingMultiframe) {
        existingMultiframe.restoreWindowFromMaximized();
        existingMultiframe.restoreMinimized();
        existingMultiframe.moveInForeground();

    } else {
        const newInstance = new multiframe(url, title, startMaximized, borderColor, counter, counter, height, width, multiframes.length + zIndexOffset, roomUid);
        newInstance.addEventListener('remove', () => {
            removeMultiframe(newInstance);
        });
        newInstance.addEventListener('addInteraction', () => {
            addInteractions(newInstance.frame);
        });
        newInstance.addEventListener('removeInteraction', () => {
            removeInteraction(newInstance.frame);
        });
        newInstance.addEventListener('incrementZindex', () => {
            zIndex++;
        });
        newInstance.addEventListener('createNewMultiframe', (data) => {
            createIframe(data.url, data.title, data.maximize, '', data.roomuid)
        });
        newInstance.addEventListener('blockUrlForMultiframe', (data) => {
            blockedUrls.push(data.url);
        });
        newInstance.addEventListener('openNewMultiframe', (data) => {
            sendViaWebsocket('openNewIframe',JSON.stringify(data));
        });
        multiframes.push(newInstance);

    }
    counter += 40;

    if (isFullscreen()) {
        if (document) {
            document.exitFullscreen();
        }
    }
}

function checkIfUrlIsBlocked(url) {
    return blockedUrls.includes(url)
}

function multiframeCheck(random) {
    return multiframes.find(instance => instance.random === random);
}

function getMultiframeFromHtmlFrame(frame) {
    const res = multiframes.find(instance => instance.frame === frame);
    return res;
}

function getotherFramesNotActual(instance) {
    const res = multiframes.filter(frame => frame !== instance);
    return res;
}

function removeMultiframe(instance) {
    multiframes = multiframes.filter(i => i !== instance);

}

function isFullscreen() {
    var st = screen.top || screen.availTop || window.screenTop;
    if (st != window.screenY) {
        return false;
    }
    return window.fullScreen == true || screen.height - document.documentElement.clientHeight <= 15;
}

function removeInteraction() {
    if (interaction) {
        interaction.unset();
        interaction = null;
    }
}

function initInteractionFrame(ele) {
    var t = ele.target.closest('.jitsiadminiframe');

    if (t && t.style.width !== '100%' && !t.classList.contains('minified') && dragactive === false) {
        addInteractions(ele.target.closest('.jitsiadminiframe'));
        if (ele.target.classList.contains('dragger')) {
            switchDragOn();
        } else {
            switchDragOff();
        }
    }
}

function switchDragOn() {
    if (interaction) {
        interaction.draggable(true);
        return null;
    }
}

function switchDragOff() {
    if (interaction) {
        interaction.draggable(false);
        return null;
    }
}

function moveActualToForeground(actualFrame) {
  if (actualFrame.isMutable) {
    actualFrame.playFrame();
    // Iteriere durch alle multiframes und pausiere die anderen mutablen Frames
    multiframes.forEach((frame) => {
      if (frame !== actualFrame && frame.isMutable) {
        frame.pauseFrame(); // Pausiere das Frame
      }
    });
  }

  const totalFrames = multiframes.length;

  // Setze das z-index des aktuellen Frames auf die Anzahl der Frames (höchstes z-index)
  actualFrame.setZindex(totalFrames + zIndexOffset);
  actualFrame.moveInForeground();
  // Sortiere die anderen Frames nach ihrem aktuellen z-index
  const otherFrames = getotherFramesNotActual(actualFrame)
    .filter((frame) => frame !== actualFrame)
    .sort((a, b) => a.zIndex - b.zIndex);

  // Vergib die z-index-Werte beginnend bei 1
  let zIndex = 1;
  otherFrames.forEach((frame) => {
    frame.setZindex(zIndex + zIndexOffset);
    zIndex++;
  });
}

function addInteractions(ele) {
  if (interaction?.target === ele) {
    return null;
  }

  if (interaction) {
    interaction.unset();
  }

  const position = {
    x: Number.parseFloat(ele.dataset.x),
    y: Number.parseFloat(ele.dataset.y),
  };

  interaction = interact(ele)
    .draggable({
      enabled: false,
      allowFrom: ".dragger",
      listeners: {
        start: (event) => {
          dragactive = true;
          event.stopPropagation();
          if (event.target.classList.contains("minified")) {
            return null;
          }
          makeBlury(event.target);
          addOverlayOverAllMultiframes();
          moveActualToForeground(getMultiframeFromHtmlFrame(event.target));
          position.x = Number.parseFloat(event.target.dataset.x) || 0;
          position.y = Number.parseFloat(event.target.dataset.y) || 0;
          tryfullscreen = false;
          event.target.querySelector(".button-maximize").dataset.maximal = "0";

          event.target
            .querySelector(".button-maximize i")
            .classList.remove("fa-window-restore");
          event.target
            .querySelector(".button-maximize i")
            .classList.add("fa-window-maximize");
        },
        move: (event) => {
          if (event.target.classList.contains("minified")) {
            return null;
          }

          tryfullscreen = false;
          if (
            event.clientX >= window.innerWidth - 20 &&
            event.clientY >= 20 &&
            event.clientY <= window.innerHeight - 20
          ) {
            //on the left side

            position.x = window.innerWidth / 2;
            position.y = 0;
            event.target.style.height = window.innerHeight + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (
            event.clientX >= window.innerWidth - 20 &&
            event.clientY <= 20
          ) {
            //on the left side up

            position.x = window.innerWidth / 2;
            position.y = 0;
            event.target.style.height = window.innerHeight / 2 + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (
            event.clientX >= window.innerWidth - 20 &&
            event.clientY >= window.innerHeight - 20
          ) {
            //on the left side down

            position.x = window.innerWidth / 2;
            position.y = window.innerHeight / 2;
            event.target.style.height = window.innerHeight / 2 + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (event.clientX <= 20 && event.clientY <= 20) {
            //on the right side up

            position.x = 0;
            position.y = 0;
            event.target.style.height = window.innerHeight / 2 + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (
            event.clientX <= 20 &&
            event.clientY >= window.innerHeight - 20
          ) {
            //on the right side down

            position.x = 0;
            position.y = window.innerHeight / 2;
            event.target.style.height = window.innerHeight / 2 + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (
            event.clientX <= 20 &&
            event.clientY >= 20 &&
            event.clientY <= window.innerHeight - 20
          ) {
            //on the right side

            position.x = 0;
            position.y = 0;
            event.target.style.height = window.innerHeight + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else if (
            event.clientX >= 20 &&
            event.clientY >= window.innerHeight - 20 &&
            event.clientX <= window.innerWidth - 20
          ) {
            //bottom

            position.x = 0;
            position.y = window.innerHeight / 2;
            event.target.style.height = window.innerHeight / 2 + "px";
            event.target.style.width = window.innerWidth + "px";
          } else if (
            event.clientX >= 20 &&
            event.clientY <= 20 &&
            event.clientX <= window.innerWidth - 20
          ) {
            //top
            event.target.style.height = "100vh";
            event.target.style.width = "100%";

            position.x = 0;
            position.y = 0;
            tryfullscreen = true;
          } else if (
            event.clientX <= 0 &&
            event.clientY >= 0 &&
            event.clientY <= window.innerHeight - 20
          ) {
            //on the right side

            position.x = 0;
            position.y = 0;
            event.target.style.height = window.innerHeight + "px";
            event.target.style.width = window.innerWidth / 2 + "px";
          } else {
            position.x += event.dx;
            position.y += event.dy;
          }

          if (position.x !== null) {
            event.target.style.transform = `translate(${position.x}px, ${position.y}px)`;
          }
        },
        end: (event) => {
          removeBlury(event.target);
          removeOverlayFromAllMultiframes();
          var ifr = event.target.querySelector(".multiframeIframe");
          ifr.style.removeProperty("display");
          if (event.target.classList.contains("minified")) {
            return null;
          }
          event.target.dataset.x = position.x;
          event.target.dataset.y = position.y;
          dragactive = false;
          if (tryfullscreen === true) {
            //top
            position.y = 5;
            event.target.style.transform = `translate(${position.x}px, ${position.y}px)`;
            event.target.dataset.x = position.x;
            event.target.dataset.y = position.y;
            getMultiframeFromHtmlFrame(event.target).maximizeWindow();
          }
        },
      },
    })
    .resizable({
      edges: { left: true, right: true, bottom: true, top: true },
      listeners: {
        start: ({ target }) => {
          dragactive = true;
          moveActualToForeground(getMultiframeFromHtmlFrame(target));
          makeBlury(target);
          addOverlayOverAllMultiframes();
        },
        move: (event) => {
          if (
            event.target.classList.contains("minified") ||
            event.clientX < 0 ||
            event.clientX > window.innerWidth ||
            event.clientY > window.innerHeight ||
            event.clientY < 0
          ) {
            return null;
          }

          position.x += event.deltaRect.left;
          position.y += event.deltaRect.top;
          event.target.style.width = `${event.rect.width}px`;
          event.target.style.height = `${event.rect.height}px`;
          event.target.style.transform = `translate(${position.x}px, ${position.y}px)`;
          event.target.dataset.x = position.x;
          event.target.dataset.y = position.y;
        },
        end: ({ target }) => {
          dragactive = false;
          removeBlury(target);
          removeOverlayFromAllMultiframes();
        },
      },
    });
}

function makeBlury(frame) {
  var content = frame.querySelector(".iframeFrame");
  content.style.visibility = "hidden";
  frame.style.opacity = 0.5;
  // for (var f of frames) {
  //     f.insertAdjacentHTML('afterbegin', '<div class="blurryOverlay" style="position: absolute; z-index: 2; height: 100%; width: 100%; opacity: 0.0; background-color: inherit"></div>');
  // }
  // frame.querySelector('.blurryOverlay').style.opacity = 0.5;
}

function removeBlury(frame) {
  var content = frame.querySelector(".iframeFrame");
  content.style.removeProperty("visibility");
  frame.style.removeProperty("opacity");
}

function checkIfIsMutable(frame) {}

// function checkIfIsMutable(frame) {
//     if (frame.classList.contains('isMutable')) {
//         var actualPause = frame.querySelector('.pauseConference')
//         var allFrames = document.querySelectorAll(".isMutable[data-muted='0']");
//         for (var a of allFrames) {
//             if (a !== frame) {
//                 var pauseButton = a.querySelector('.pauseConference');
//                 {
//                     pauseButton.click();
//                 }
//             }
//         }
//         if (frame.dataset.muted == 1) {
//             actualPause.click();
//         }
//     }
// }

function addOverlayOverAllMultiframes() {
  const iframes = document.querySelectorAll("iframe");

  iframes.forEach((iframe) => {
    const overlay = document.createElement("div");
    overlay.classList.add("iframe-overlay");
    overlay.style.position = "absolute";
    overlay.style.top = "0";
    overlay.style.left = "0";
    overlay.style.width = iframe.offsetWidth + "px";
    overlay.style.height = iframe.offsetHeight + "px";
    overlay.style.backgroundColor = "rgba(0, 0, 0, 0)"; // halbtransparent

    // overlay.style.zIndex = '9999'; // über allem anderen

    // Position relativ zum Iframe setzen
    iframe.style.position = "relative";
    iframe.parentElement.style.position = "relative";

    // Overlay hinzufügen
    iframe.parentElement.appendChild(overlay);
  });
}

function removeOverlayFromAllMultiframes() {
    document.querySelectorAll('.iframe-overlay').forEach(overlay => overlay.remove());
}

export {initStartIframe, createIframe, checkIfIsMutable}
