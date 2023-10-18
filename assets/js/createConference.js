import md5 from "blueimp-md5"
import {setSnackbar} from "./myToastr";
import Moveable from "moveable";
import {Tooltip} from 'mdb-ui-kit';
import $ from "jquery";
import {setCookie, getCookie} from './cookie'

let counter = 50;
let zindex = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;
let moveable;
let frames = [];
let dragactive = false;
let startWidth = null;
let startHeight = null;
let startx = null;
let starty = null;
let tryfullscreen = null;
let startTransform = null;
let messageTimeout = {};
let closingTimeout = {};
let multiframes = {};

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

                createIframe(target.href, target.dataset.roomname, target.dataset.close === 'simple' ? false : true, true, target.dataset.bordercolor);
            }
        }
    });

    window.addEventListener('message', function (e) {
        // Get the sent data
        const data = e.data;
        // If you encode the message in JSON before sending them,
        // then decode here
        recievecommand(data, e)
    });

    addEventListener('resize', (event) => {
        setWidthOfminified();
    });
}


function recievecommand(data, event) {
    let decoded;
    try {
        decoded = JSON.parse(data);
    } catch (e) {
        return false;
    }

    const type = decoded.type
    if (decoded.url){
        var multiframe = multiframes[decoded.url];
    }

    if (type === 'closeMe') {
        clearTimeout(closingTimeout[decoded.frameId]);
        delete closingTimeout[decoded.frameId];
        closeIframe(decoded.frameId)
        if (document.querySelectorAll('.jitsiadminiframe').length === 0) {
        }
    } else if (type === 'stopClosingMe') {
        var frameId = decoded.frameId
        clearTimeout(closingTimeout[frameId]);
        delete closingTimeout[frameId];
    } else if (type === 'openNewIframe') {
        createIframe(decoded.url, decoded.title, false);
    } else if (type === 'showPlayPause') {
        var frame = document.getElementById(decoded.frameId);
        frame.classList.add('isMutable');
        frame.dataset.muted = 0;
        frame.querySelector('.pauseConference').classList.remove('d-none');
        checkIfIsMutable(frame);
    } else if (type === 'colorBorder') {
        if (multiframe){
            multiframe.style.borderColor = decoded.color;
        }

    } else if (type === 'ack') {
        var messageId = decoded.messageId
        clearTimeout(messageTimeout[messageId]);
        delete messageTimeout[messageId];
    }
}

function createIframe(url, title, startMaximized = true, borderColor = '') {
    if (window.$chatwoot) {
        window.$chatwoot.toggleBubbleVisibility("hide"); // to hide the bubble
    }

    width = window.innerWidth * 0.75;
    height = window.innerHeight * 0.75;
    counter = (document.querySelectorAll('.jitsiadminiframe').length + 1) * 50;
    var urlPath = url.split('?')[0];
    var random = md5(urlPath);
    if (document.getElementById('jitsiadminiframe' + random)) {
        return null;
    }

    var html =
        '<div id="jitsiadminiframe' + random + '" class="jitsiadminiframe" data-x="' + counter + '" data-y="' + counter + '" style="border-color: ' + borderColor + '">' +
        '<div class="headerBar">' +
        '<div class="dragger"><i class="fa-solid fa-arrows-up-down-left-right me-2"></i>' + title + '</div>' +
        '<div class="actionIconLeft">' +
        '<div class="pauseConference d-none  actionIcon" data-pause="0"><i class="fa-solid fa-pause" data-mdb-toggle="tooltip" title="Pause"></i></div> ' +
        '<div class="minimize  actionIcon"><i class="fa-solid fa-window-minimize" data-mdb-toggle="tooltip" title="Minimize"></i></div> ' +
        '<div class="button-restore actionIcon d-none" data-maximal="0" data-mdb-toggle="tooltip" title="Restore"><i class="fa-solid fa-window-restore"></i></div> ' +
        '<div class="button-maximize  actionIcon" data-maximal="0" data-mdb-toggle="tooltip" title="Maximize"><i class="fa-solid fa-window-maximize"></i></div> ' +
        (document.fullscreenEnabled ? '<div class="button-fullscreen actionIcon" data-maximal="0" data-mdb-toggle="tooltip" title="Fullscreen"><i class="fa-solid fa-expand"></i></div> ' : '') +
        '<div class="closer  actionIcon"><i class="fa-solid fa-xmark" data-mdb-toggle="tooltip" title="Exit"></i></div> ' +
        '</div>' +
        '</div>' +
        '<div class="iframeFrame">' +
        '<iframe  class="multiframeIframe"></iframe>' +
        '</div>' +
        '</div> ';

    var site = url;

    if (document.getElementById('window')) {
        document.getElementById('window').insertAdjacentHTML('beforeend', html);
    } else {
        document.querySelector('body').insertAdjacentHTML('beforeend', html);
    }
    $('[data-mdb-toggle="tooltip"]').tooltip();
    var multiframe = document.getElementById('jitsiadminiframe' + random)
    multiframes[site] = multiframe;
    multiframe.style.transform = 'translate(' + counter + 'px, ' + counter + 'px)';
    multiframe.style.width = width + 'px';
    multiframe.style.height = height + 'px';
    multiframe.style.zIndex = zindex++;
    multiframe.querySelector('iframe').src = site;
    multiframe.querySelector('.button-maximize').dataset.maximal = "0";
    multiframe.querySelector('.closer').dataset.id = 'jitsiadminiframe' + random;
    multiframe.addEventListener('dblclick', function (e) {
        toggleMaximize(e);
    })
    multiframe.querySelector('.closer').addEventListener('click', function (e) {
        e.stopPropagation();
        closeFrame(e);

    })

    multiframe.querySelector('.minimize').addEventListener('click', function (e) {
        e.stopPropagation();
        minimizeFrame(e)
        removeInteraction();
    })

    multiframe.querySelector('.pauseConference').addEventListener('click', function (e) {
        e.stopPropagation();
        pauseIframe(e);
    })


    multiframe.querySelector('.button-fullscreen').addEventListener('click', function (e) {
        e.stopPropagation();
        fulscreenWindow(e.currentTarget.closest('.jitsiadminiframe').querySelector('iframe'));
    })

    multiframe.querySelector('.button-maximize').addEventListener('click', function (e) {
        e.stopPropagation();

        prepareMaximize(e);
        maximizeWindow(e.target);
        removeInteraction();
    })

    multiframe.querySelector('.button-restore').addEventListener('click', function (e) {
        restoreWindow(e);
        removeInteraction();
    });

    multiframe.addEventListener('click', function (e) {
        moveInForeground(event.currentTarget);
    })
    setTimeout(function () {
        sendCommand('jitsiadminiframe' + random, {type: 'init'});
    }, 7000)
    counter += 40;
    if (startMaximized) {
        if (getCookie('startMaximized') && getCookie('startMaximized') == 1) {
            document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').click();
        }
        if (!getCookie('startMaximized')) {
            document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').click();
        }
    }

    if (isFullscreen()) {
        document.exitFullscreen();
        var iframe = document.getElementById('jitsiadminiframe' + random).querySelector('iframe');
        fulscreenWindow(iframe);
    }
}

function isFullscreen() {
    var st = screen.top || screen.availTop || window.screenTop;
    if (st != window.screenY) {
        return false;
    }
    return window.fullScreen == true || screen.height - document.documentElement.clientHeight <= 30;
}

function closeFrame(e) {
    var id = e.currentTarget.dataset.id;
    sendCommand(id, {type: 'pleaseClose'})
    closingTimeout[id] = setTimeout(function () {
        closeIframe(id);
    }, 100);
}

function toggleMaximize(e) {
    var element = e.currentTarget
    if (element.classList.contains('maximized')) {
        restoreWindow(e)
    } else {
        prepareMaximize(e)
        maximizeWindow(element)
    }
}

function fulscreenWindow(element) {
    element.requestFullscreen();
}

function pauseIframe(e) {

    var currentElement = e.currentTarget;

    // Aktivieren Sie die Tonwiedergabe im iFrame
    if (currentElement.dataset.pause == 0) {
        currentElement.closest('.isMutable').dataset.muted = 1;
        currentElement.dataset.pause = 1;
        currentElement.innerHTML = '<i class="fa-solid fa-play"></i>';
        currentElement.closest('.jitsiadminiframe').querySelector('.iframeFrame').insertAdjacentHTML('afterbegin', '<div class="pausedFrame"><i class="fa-solid fa-circle-pause"></i></div>');
        sendCommand(currentElement.closest('.jitsiadminiframe').id, {type: 'pauseIframe'})
    } else {
        currentElement.closest('.isMutable').dataset.muted = 0;
        currentElement.dataset.pause = 0;
        currentElement.innerHTML = '<i class="fa-solid fa-pause"></i>';
        currentElement.closest('.jitsiadminiframe').querySelector('.pausedFrame').remove();
        sendCommand(currentElement.closest('.jitsiadminiframe').id, {type: 'playIframe'})
    }
}

function prepareMaximize(e) {
    startx = parseInt(e.target.closest('.jitsiadminiframe').dataset.x);
    starty = parseInt(e.target.closest('.jitsiadminiframe').dataset.y);
    startHeight = e.target.closest('.jitsiadminiframe').offsetHeight;
    startWidth = e.target.closest('.jitsiadminiframe').offsetWidth;
    startTransform = e.target.closest('.jitsiadminiframe').style.transform;
}

function maximizeWindow(e) {
    setCookie('startMaximized', 1, 365)
    var frame = e.closest('.jitsiadminiframe');
    var maxiIcon = frame.querySelector('.button-maximize');
    var restoreButton = frame.querySelector('.button-restore');
    if (maxiIcon.dataset.maximal === "0") {
        maxiIcon.dataset.height = startHeight;
        maxiIcon.dataset.width = startWidth;
        maxiIcon.dataset.translation = startTransform;
        maxiIcon.dataset.x = startx;
        maxiIcon.dataset.y = starty;
        frame.style.width = "100%";
        frame.style.height = "100%";
        frame.style.transform = 'translate(0px, 0px)'
        frame.style.borderWidth = '0px'
        frame.classList.add('maximized');
        frame.querySelector('.headerBar').style.padding = '8px'
        restoreButton.classList.remove('d-none');
        maxiIcon.classList.add('d-none');
        maxiIcon.dataset.maximal = "1";
    }

}

function restoreWindow(e) {
    setCookie('startMaximized', 0, 365)
    var frame = e.currentTarget.closest('.jitsiadminiframe');
    var maxiIcon = frame.querySelector('.button-maximize');
    var restoreButton = frame.querySelector('.button-restore');
    if (maxiIcon.dataset.maximal === "1") {
        frame.style.width = maxiIcon.dataset.width + 'px';
        frame.style.height = maxiIcon.dataset.height + 'px';
        frame.style.transform = maxiIcon.dataset.translation;
        frame.style.removeProperty('border-width');
        frame.querySelector('.headerBar').style.removeProperty('padding');
        frame.dataset.x = maxiIcon.dataset.x;
        frame.dataset.y = maxiIcon.dataset.y;
        maxiIcon.dataset.maximal = "0";
        maxiIcon.classList.remove('d-none');
        restoreButton.classList.add('d-none');
        frame.classList.remove('maximized');
    }
}

let messages = {};

function sendCommand(id, message) {
    var ele = document.getElementById(id).querySelector('iframe');
    var messageId = makeid(32);
    message.frameId = id;
    message.scope = 'jitsi-admin-iframe';
    message.messageId = messageId;
    ele.contentWindow.postMessage(JSON.stringify(message), '*');
    messages[messageId] = id;
    messageTimeout[messageId] = setTimeout(closeWhenNoAck, 10000, id)
}

function closeWhenNoAck(messageId) {
    closeIframe(messages[messageId]);
}


function closeIframe(id) {
    var $iframe = document.getElementById(id);
    if ($iframe) {
        document.getElementById(id).remove();
        removeInteraction();
        $('.tooltip').remove();
        if (window.$chatwoot) {
            var $iframes = document.querySelectorAll('.jitsiadminiframe');

            if ($iframes.length == 0) {
                window.$chatwoot.toggleBubbleVisibility("show"); // to hide the bubble
            }

        }

    }

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

        position.x = parseInt(event.target.closest('.jitsiadminiframe').dataset.x)
        position.y = parseInt(event.target.closest('.jitsiadminiframe').dataset.y)
        startx = position.x;
        starty = position.y;
        startWidth = event.target.closest('.jitsiadminiframe').offsetWidth;
        startHeight = event.target.closest('.jitsiadminiframe').offsetHeight;
        startTransform = event.target.closest('.jitsiadminiframe').style.transform
        tryfullscreen = false;
        // event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').dataset.maximal = "0";
        // event.target.closest('.jitsiadminiframe').style.removeProperty('border');

        event.target.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.remove('fa-window-restore');
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.add('fa-window-maximize');
    }).on("drag", event => {
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        moveInForeground(event.target.closest('.jitsiadminiframe'));

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
            maximizeWindow(event.target);

        }

    });

    let frame = {
        translate: [0, 0],
    };

    moveable.on("resizeStart", ({target, clientX, clientY}) => {
        dragactive = true;

        makeBlury(target.closest('.jitsiadminiframe'));
    }).on("resize", event => {

            if (event.target.classList.contains('minified') || event.clientX < 0 || event.clientX > window.innerWidth || event.clientY > window.innerHeight || event.clientY < 0) {
                return null;
            }
            moveInForeground(event.target.closest('.jitsiadminiframe'));

            const beforeTranslate = event.drag.beforeTranslate;

            frame.translate = beforeTranslate;
            event.target.style.width = `${event.width}px`;
            event.target.style.height = `${event.height}px`;
            event.target.style.transform = `translate(${beforeTranslate[0]}px, ${beforeTranslate[1]}px)`;
            // event.target.style.removeProperty('border');
            event.target.querySelector('.headerBar').style.removeProperty('padding');

            event.target.dataset.x = beforeTranslate[0];
            event.target.dataset.y = beforeTranslate[1];
        }
    ).on("resizeEnd", ({target, isDrag, clientX, clientY}) => {

        dragactive = false;
        removeBlury(target.closest('.jitsiadminiframe'));
    });

}

function minimizeFrame(e) {
    moveToMinibar(e.currentTarget.closest('.jitsiadminiframe'));
}

function moveToMinibar(container) {
    // container.dataset.parent = container.parentNode.id;
    // var minimizeBar = document.getElementById('minimizeBar');
    // minimizeBar.append(container);
    if (container.classList.contains('minified')) {
        return null;
    }
    container.insertAdjacentHTML('afterbegin', '<div class="minimizeOverlay" style="position: absolute; z-index: 2; height: 100%; width: 100%; opacity: 0.0; background-color: inherit; cursor: pointer"></div>');
    container.querySelector('iframe').style.height = '0px';
    container.dataset.beforeminwidth = container.style.width;
    container.classList.add('minified');
    container.querySelector('.minimizeOverlay').addEventListener('click', removeFromMinibar);


    setWidthOfminified();
    container.querySelector('iframe').style.removeProperty('height');
}

function setWidthOfminified() {
    var ele = document.querySelectorAll('.minified');
    var leftCounter = 0
    for (var e of ele) {
        e.style.width = window.innerWidth / ele.length + 'px';
        e.style.left = leftCounter + 'px';
        leftCounter += window.innerWidth / ele.length;
    }
}

function removeFromMinibar(e) {
    if (e.target.closest('.actionIcon')) {
        return null;
    }
    e.currentTarget.removeEventListener('click', removeFromMinibar);

    var container = e.currentTarget.closest('.jitsiadminiframe');
    if (container.classList.contains('minified')) {
        container.classList.remove('minified');
        container.style.width = container.dataset.beforeminwidth;
        container.style.removeProperty('left');
        setWidthOfminified();
        addInteractions(container)
    }
    e.currentTarget.remove();
    removeInteraction();
}

function makeid(length) {
    var result = '';
    var characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789';
    var charactersLength = characters.length;
    for (var i = 0; i < length; i++) {
        result += characters.charAt(Math.floor(Math.random() *
            charactersLength));
    }
    return result;
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


function moveInForeground(frame) {
    if (frame.style.zIndex < zindex - 1) {
        frame.style.zIndex = zindex++;
    }
    checkIfIsMutable(frame);

}

function checkIfIsMutable(frame) {
    if (frame.classList.contains('isMutable')) {
        var actualPause = frame.querySelector('.pauseConference')
        var allFrames = document.querySelectorAll(".isMutable[data-muted='0']");
        for (var a of allFrames) {
            if (a !== frame) {
                var pauseButton = a.querySelector('.pauseConference');
                {
                    pauseButton.click();
                }
            }
        }
        if (frame.dataset.muted == 1) {
            actualPause.click();
        }
    }
}

export {initStartIframe, createIframe}
