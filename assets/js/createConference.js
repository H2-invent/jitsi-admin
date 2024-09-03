import md5 from "blueimp-md5"
import {setSnackbar} from "./myToastr";
import Moveable from "moveable";
import {Tooltip} from 'mdb-ui-kit';
import $ from "jquery";
import {setCookie, getCookie} from './cookie'
import {multiframe} from "./multiframe";


let counter = 50;
let zIndexOffset = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;
let moveable;
let dragactive = false;
let multiframes = [];
let startx = null;
let starty = null;
let startWidth = null;
let startHeight = null;
let startTransform = null;
let tryfullscreen = null;


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
                setSnackbar(target.dataset.iframetoast, 'danger');
            } else {
                const isMaximized= getCookie('startMaximized')?getCookie('startMaximized'):1;
                createIframe(target.href, target.dataset.roomname, isMaximized == 1, true, target.dataset.bordercolor);
            }
        }
    });


    // addEventListener('resize', (event) => {
    //     setWidthOfminified();
    // });
}



function createIframe(url, title, startMaximized = true, borderColor = '') {

    width = window.innerWidth * 0.75;
    height = window.innerHeight * 0.75;
    counter = (document.querySelectorAll('.jitsiadminiframe').length + 1) * 50;

    var urlPath = url.split('?')[0];
    var random = md5(urlPath);

    const existingMultiframe = multiframeCheck(random);
    if (existingMultiframe){
        existingMultiframe.restoreWindowFromMaximized();
        existingMultiframe.restoreMinimized();
        existingMultiframe.moveInForeground();

    }else {
        const newInstance = new multiframe(url,title,startMaximized,borderColor,counter,counter,height,width,multiframes.length+zIndexOffset);
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
          createIframe(data.url,data.title,data.maximize)
        });
        multiframes.push(newInstance);

    }
    counter += 40;

    if (isFullscreen()) {
        document.exitFullscreen();

    }
}
function multiframeCheck(random) {
    return multiframes.some(instance => instance.random === random);
}
function getMultiframeFromHtmlFrame(frame) {
    const res= multiframes.find(instance => instance.frame === frame);
    return res;
}
function getotherFramesNotActual(instance) {
    const res = multiframes.filter(frame => frame !== instance);
    return res;
}
function removeMultiframe(instance) {
    multiframes = multiframes.filter(i => i !== instance);
    console.log(`Instanz mit der random-ID ${instance.random} wurde entfernt.`);
}

function isFullscreen() {
    var st = screen.top || screen.availTop || window.screenTop;
    if (st != window.screenY) {
        return false;
    }
    return window.fullScreen == true || screen.height - document.documentElement.clientHeight <= 15;
}

function removeInteraction() {
    if (moveable) {
        moveable.destroy();
        moveable = null;
    }
}

function initInteractionFrame(ele) {
    var t = ele.target.closest('.jitsiadminiframe')
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
    if (moveable) {
        moveable.draggable = true;
        return null;
    }
}

function switchDragOff() {
    if (moveable) {
        moveable.draggable = false;
        return null;
    }
}
function moveActualToForeground(actualFrame) {
    if (actualFrame.isMutable) {
        actualFrame.playFrame();
        // Iteriere durch alle multiframes und pausiere die anderen mutablen Frames
        multiframes.forEach(frame => {
            if (frame !== actualFrame && frame.isMutable) {
                frame.pauseFrame(); // Pausiere das Frame
            }
        });
    }

    const totalFrames = multiframes.length;

    // Setze das z-index des aktuellen Frames auf die Anzahl der Frames (höchstes z-index)
    actualFrame.setZindex(totalFrames +zIndexOffset);
    actualFrame.moveInForeground();
    // Sortiere die anderen Frames nach ihrem aktuellen z-index
    const otherFrames = getotherFramesNotActual(actualFrame)
        .filter(frame => frame !== actualFrame)
        .sort((a, b) => a.zIndex - b.zIndex);

    // Vergib die z-index-Werte beginnend bei 1
    let zIndex = 1;
    otherFrames.forEach(frame => {
        frame.setZindex(zIndex+zIndexOffset);
        zIndex++;
    });
}
function addInteractions(ele) {

    const position = {x: counter, y: counter}
    if (moveable) {
        moveable.target = ele;
        return null;
    }

    moveable = new Moveable(document.body, {
        target: ele,
        draggable: false,
        resizable: true,
        edge: true,
        origin: false,
    });

    moveable.on("dragStart", event => {
        dragactive = true;
        event.inputEvent.stopPropagation();
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        makeBlury(event.target.closest('.jitsiadminiframe'))
        moveActualToForeground(getMultiframeFromHtmlFrame(event.target));
        position.x = parseInt(event.target.closest('.jitsiadminiframe').dataset.x)
        position.y = parseInt(event.target.closest('.jitsiadminiframe').dataset.y)
        startx = position.x;
        starty = position.y;
        startWidth = event.target.closest('.jitsiadminiframe').offsetWidth;
        startHeight = event.target.closest('.jitsiadminiframe').offsetHeight;
        startTransform = event.target.closest('.jitsiadminiframe').style.transform
        tryfullscreen = false;
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').dataset.maximal = "0";

        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.remove('fa-window-restore');
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.add('fa-window-maximize');
    }).on("drag", event => {
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }


        tryfullscreen = false;
        if (event.clientX >= window.innerWidth - 20 && event.clientY >= 20 && event.clientY <= window.innerHeight - 20) {//on the left side

            position.x = window.innerWidth / 2;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX >= window.innerWidth - 20 && event.clientY <= 20) {//on the left side up

            position.x = window.innerWidth / 2;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight / 2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX >= window.innerWidth - 20 && event.clientY >= window.innerHeight - 20) {//on the left side down

            position.x = window.innerWidth / 2;
            position.y = window.innerHeight / 2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight / 2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX <= 20 && event.clientY <= 20) {//on the right side up

            position.x = 0;
            position.y = 0;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight / 2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'
        } else if (event.clientX <= 20 && event.clientY >= window.innerHeight - 20) {//on the right side down

            position.x = 0;
            position.y = window.innerHeight / 2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight / 2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX <= 20 && event.clientY >= 20 && event.clientY <= window.innerHeight - 20) {//on the right side

            position.x = 0;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'


        } else if (event.clientX >= 20 && event.clientY >= window.innerHeight - 20 && event.clientX <= window.innerWidth - 20) {//bottom

            position.x = 0;
            position.y = window.innerHeight / 2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight / 2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth + 'px'


        } else if (event.clientX >= 20 && event.clientY <= 20 && event.clientX <= window.innerWidth - 20) {//top
            event.target.closest('.jitsiadminiframe').style.height = "100vh";
            event.target.closest('.jitsiadminiframe').style.width = "100%";

            position.x = 0;
            position.y = 0;
            tryfullscreen = true;
        } else if (event.clientX <= 0 && event.clientY >= 0 && event.clientY <= window.innerHeight - 20) {//on the right side

            position.x = 0;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'


        } else {
            position.x += event.delta[0];
            position.y += event.delta[1]
        }

        if (position.x !== null) {
            event.target.closest('.jitsiadminiframe').style.transform =
                `translate(${position.x}px, ${position.y}px)`
        }


    }).on("dragEnd", event => {

        removeBlury(event.target.closest('.jitsiadminiframe'))
        var ifr = event.target.closest('.jitsiadminiframe').querySelector('.multiframeIframe');
        ifr.style.removeProperty('display');
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        event.target.closest('.jitsiadminiframe').dataset.x = position.x;
        event.target.closest('.jitsiadminiframe').dataset.y = position.y;
        dragactive = false;
        if (!event.isDrag) {
            if (!event.isDrag && event.inputEvent.target.click instanceof Function) {
                event.inputEvent.target.click()
            }
        }
        if (tryfullscreen === true) {//top
            position.y = 5;
            event.target.closest('.jitsiadminiframe').style.transform =
                `translate(${position.x}px, ${position.y}px)`
            event.target.closest('.jitsiadminiframe').dataset.x = position.x;
            event.target.closest('.jitsiadminiframe').dataset.y = position.y;
            getMultiframeFromHtmlFrame(event.target).maximizeWindow();

        }

    });

    let frame = {
        translate: [0, 0],
    };

    moveable.on("resizeStart", ({target, clientX, clientY}) => {
        dragactive = true;
        moveActualToForeground(getMultiframeFromHtmlFrame(target));
        makeBlury(target.closest('.jitsiadminiframe'));
    }).on("resize", event => {

            if (event.target.classList.contains('minified') || event.clientX < 0 || event.clientX > window.innerWidth || event.clientY > window.innerHeight || event.clientY < 0) {
                return null;
            }

            const beforeTranslate = event.drag.beforeTranslate;

            frame.translate = beforeTranslate;
            event.target.style.width = `${event.width}px`;
            event.target.style.height = `${event.height}px`;
            event.target.style.transform = `translate(${beforeTranslate[0]}px, ${beforeTranslate[1]}px)`;
            // event.target.style.removeProperty('border');


            event.target.dataset.x = beforeTranslate[0];
            event.target.dataset.y = beforeTranslate[1];
        }
    ).on("resizeEnd", ({target, isDrag, clientX, clientY}) => {

        dragactive = false;
        removeBlury(target.closest('.jitsiadminiframe'));
    });

}


function makeBlury(frame) {
    var frames = document.querySelectorAll('.iframeFrame, #frame');
    for (var f of frames) {
        f.insertAdjacentHTML('afterbegin', '<div class="blurryOverlay" style="position: absolute; z-index: 2; height: 100%; width: 100%; opacity: 0.0; background-color: inherit"></div>');
    }
    frame.querySelector('.blurryOverlay').style.opacity = 0.5;
}

function removeBlury(frame) {
    var frames = document.querySelectorAll('.blurryOverlay');
    for (var f of frames) {
        f.remove();
    }
}

function checkIfIsMutable(frame) {

}
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

export {initStartIframe, createIframe,  checkIfIsMutable}
