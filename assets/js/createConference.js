import interact from 'interactjs'
import {leaveMeeting} from "./websocket";
import md5 from "blueimp-md5"
import {setSnackbar} from "./myToastr";
import Moveable from "moveable";

let counter = 50;
let zindex = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;
let moveable;
let frames = [];
let dragactive = false;

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
                createIframe(target.href, target.dataset.roomname, target.dataset.close === 'simple' ? false : true);
            }

        }
    });

    window.addEventListener('message', function (e) {
        // Get the sent data
        const data = e.data;

        // If you encode the message in JSON before sending them,
        // then decode here
        recievecommand(data)

    });
    addEventListener('resize', (event) => {
        setWidthOfminified();
    });
}

function createIframe(url, title, closeIntelligent = true) {

    width = window.innerWidth * 0.75;
    height = window.innerHeight * 0.75;
    counter = (document.querySelectorAll('.jitsiadminiframe').length + 1) * 50;
    var urlPath = url.split('?')[0];
    var random = md5(urlPath);
    if (document.getElementById('jitsiadminiframe' + random)) {
        return null;
    }
    var html =
        '<div id="jitsiadminiframe' + random + '" class="jitsiadminiframe" data-x="' + counter + '" data-y="' + counter + '">' +
        '<div class="headerBar">' +
        '<div class="dragger"><i class="fa-solid fa-arrows-up-down-left-right me-2"></i>' + title + '</div>' +
        '<div class="actionIconLeft">' +
        '<div class="minimize  actionIcon"><i class="fa-solid fa-window-minimize"></i></div> ' +
        '<div class="button-restore actionIcon d-none" data-maximal="0"><i class="fa-solid fa-window-restore"></i></div> ' +
        '<div class="button-maximize  actionIcon" data-maximal="0"><i class="fa-solid fa-window-maximize"></i></div> ' +
        (document.fullscreenEnabled ? '<div class="button-fullscreen actionIcon" data-maximal="0"><i class="fa-solid fa-expand"></i></div> ' : '') +
        '<div class="closer  actionIcon"><i class="fa-solid fa-xmark"></i></div> ' +
        '</div>' +
        '</div>' +
        '<iframe  ></iframe>' +
        '</div> ';

    var site = url;
    if (document.getElementById('window')) {
        document.getElementById('window').insertAdjacentHTML('beforeend', html);
    } else {
        document.querySelector('body').insertAdjacentHTML('beforeend', html);
    }

    document.getElementById('jitsiadminiframe' + random).style.transform = 'translate(' + counter + 'px, ' + counter + 'px)';
    document.getElementById('jitsiadminiframe' + random).style.width = width + 'px';
    document.getElementById('jitsiadminiframe' + random).style.height = height + 'px';
    document.getElementById('jitsiadminiframe' + random).style.zIndex = zindex++;
    document.getElementById('jitsiadminiframe' + random).querySelector('iframe').src = site;
    document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').dataset.maximal = "0";
    document.getElementById('jitsiadminiframe' + random).querySelector('.closer').dataset.id = 'jitsiadminiframe' + random;

    document.getElementById('jitsiadminiframe' + random).querySelector('.closer').addEventListener('click', function (e) {
        closeFrame(e, closeIntelligent);

    })

    document.getElementById('jitsiadminiframe' + random).querySelector('.minimize').addEventListener('click', function (e) {
        minimizeFrame(e)
        removeInteraction();
    })

    document.getElementById('jitsiadminiframe' + random).querySelector('.button-fullscreen').addEventListener('click', function (e) {
        e.currentTarget.closest('.jitsiadminiframe').querySelector('iframe').requestFullscreen();
    })

    document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').addEventListener('click', function (e) {
        maximizeWindow(e);
        removeInteraction();
    })

    document.getElementById('jitsiadminiframe' + random).querySelector('.button-restore').addEventListener('click', function (e) {
        restoreWindow(e);
        removeInteraction();
    });

    document.getElementById('jitsiadminiframe' + random).addEventListener('click', function (e) {
        if (event.currentTarget.style.zIndex < zindex - 1) {
            event.currentTarget.style.zIndex = zindex++;
        }
    })

    setTimeout(function () {
        sendCommand('jitsiadminiframe' + random, {type: 'init'});
    }, 5000)
    counter += 40;
    if (window.innerWidth < 992) {
        document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').click();
    }

}

function closeFrame(e, closeIntelligent, random) {

    if (!e.currentTarget.hasAttribute('data-close-blocker') || e.currentTarget.closeBlocker === '0') {
        if (closeIntelligent) {
            var id = e.currentTarget.dataset.id;
            sendCommand(id, {type: 'pleaseClose'})
        } else {
            closeIframe(e.currentTarget.closest('.jitsiadminiframe').id);
        }
    }

    let current = e.currentTarget;
    current.dataset.closeBlocker = '1';
    setTimeout(function () {
        current.dataset.closeBlocker = '0';
    }, 500)
}

function maximizeWindow(e) {

    var frame = e.currentTarget.closest('.jitsiadminiframe');
    var maxiIcon = frame.querySelector('.button-maximize');
    var restoreButton = frame.querySelector('.button-restore');
    if (maxiIcon.dataset.maximal === "0") {
        maxiIcon.dataset.height = e.currentTarget.closest('.jitsiadminiframe').style.height;
        maxiIcon.dataset.width = e.currentTarget.closest('.jitsiadminiframe').style.width;
        maxiIcon.dataset.translation = e.currentTarget.closest('.jitsiadminiframe').style.transform;
        maxiIcon.dataset.x = e.currentTarget.closest('.jitsiadminiframe').dataset.x;
        maxiIcon.dataset.y = e.currentTarget.closest('.jitsiadminiframe').dataset.y;
        frame.style.width = "100%";
        frame.style.height = "100vh";
        frame.style.transform = 'translate(0px, 0px)'
        frame.style.borderWidth = '0px'
        frame.querySelector('.headerBar').style.padding = '8px'
        restoreButton.classList.remove('d-none');
        maxiIcon.classList.add('d-none');
        maxiIcon.dataset.maximal = "1";
    }

}

function restoreWindow(e) {
    var frame = e.currentTarget.closest('.jitsiadminiframe');
    var maxiIcon = frame.querySelector('.button-maximize');
    var restoreButton = frame.querySelector('.button-restore');
    if (maxiIcon.dataset.maximal === "1") {
        frame.style.width = maxiIcon.dataset.width;
        frame.style.height = maxiIcon.dataset.height;
        frame.style.transform = maxiIcon.dataset.translation;
        frame.style.removeProperty('border-width');
        frame.querySelector('.headerBar').style.removeProperty('padding');
        frame.dataset.x = maxiIcon.dataset.x;
        frame.dataset.y = maxiIcon.dataset.y;
        maxiIcon.dataset.maximal = "0";
        maxiIcon.classList.remove('d-none');
        restoreButton.classList.add('d-none');
    }
}

function sendCommand(id, message) {
    var ele = document.getElementById(id).querySelector('iframe');
    message.frameId = id;
    ele.contentWindow.postMessage(JSON.stringify(message), '*');
}

function recievecommand(data) {
    const decoded = JSON.parse(data);
    const type = decoded.type

    if (type === 'closeMe') {
        closeIframe(decoded.frameId)
        if (document.querySelectorAll('.jitsiadminiframe').length === 0) {
        }
    } else if (type === 'openNewIframe') {
        createIframe(decoded.url, decoded.title, false);
    }
}

function closeIframe(id) {
    document.getElementById(id).remove();
    removeInteraction();

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
        draggable: true,
        resizable: true,
        edge: true,
        origin: false,
    });
    moveable.on("dragStart", event => {
        dragactive = true;
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        position.x = parseInt(event.target.closest('.jitsiadminiframe').dataset.x)
        position.y = parseInt(event.target.closest('.jitsiadminiframe').dataset.y)
        // event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').dataset.maximal = "0";
        event.target.closest('.jitsiadminiframe').style.removeProperty('border');
        event.target.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.remove('fa-window-restore');
        event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.add('fa-window-maximize');
    }).on("drag", event => {
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        if (event.target.closest('.jitsiadminiframe').style.zIndex < zindex - 1) {
            event.target.closest('.jitsiadminiframe').style.zIndex = zindex++;
        }

        if (event.clientX >= window.innerWidth-10 && event.clientY >= 0 && event.clientY <= window.innerHeight) {//on the left side

            console.log('half left side')
            position.x = window.innerWidth / 2;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX >= window.innerWidth-10 && event.clientY <= 0) {//on the left side up
            console.log('left side up');
            position.x = window.innerWidth / 2;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX >= window.innerWidth-10 && event.clientY >= window.innerHeight) {//on the left side down
            console.log('left side down');
            position.x = window.innerWidth / 2;
            position.y = window.innerHeight/2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX <= 0 && event.clientY <= 0) {//on the right side up
            console.log('right side up');
            position.x = 0;
            position.y = 0;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'
        } else if (event.clientX <= 0 && event.clientY >= window.innerHeight) {//on the right side down
            console.log('right side down');
            position.x = 0;
            position.y = window.innerHeight/2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'

        } else if (event.clientX <= 0 && event.clientY >= 0 && event.clientY <= window.innerHeight) {//on the right side
            console.log('half right side')
            position.x = 0;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'


        }
        else if (event.clientX >= 0 && event.clientY >= window.innerHeight && event.clientX <= window.innerWidth) {//bottom
            console.log('bottom side')
            position.x = 0;
            position.y = window.innerHeight/2;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth + 'px'


        }
        else if (event.clientX >= 0 && event.clientY <=0 && event.clientX <= window.innerWidth) {//top
            console.log('upper side')
            position.x = 0;
            position.y = 0;
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight/2 + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth + 'px'
        }
        else if (event.clientX <= 0 && event.clientY >= 0 && event.clientY <= window.innerHeight) {//on the right side
            console.log('half right side')
            position.x = 0;
            position.y = 0
            event.target.closest('.jitsiadminiframe').style.height = window.innerHeight + 'px'
            event.target.closest('.jitsiadminiframe').style.width = window.innerWidth / 2 + 'px'


        } else {
            position.x += event.delta[0];
            position.y += event.delta[1]
        }


        event.target.closest('.jitsiadminiframe').style.transform =
            `translate(${position.x}px, ${position.y}px)`
    }).on("dragEnd", event => {
        if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
            return null;
        }
        event.target.closest('.jitsiadminiframe').dataset.x = position.x;
        event.target.closest('.jitsiadminiframe').dataset.y = position.y;
        dragactive = false;

        if (!event.isDrag && event.inputEvent.srcElement.click instanceof Function) {
            event.inputEvent.srcElement.click();


        }

        // console.log("onDragEnd", target, isDrag);
    });

    let frame = {
        translate: [0, 0],
    };

    moveable.on("resizeStart", ({target, clientX, clientY}) => {
        dragactive = true;
        // console.log("onResizeStart", target);
    }).on("resize", event => {

            if (event.target.classList.contains('minified')) {
                return null;
            }
            if (event.target.closest('.jitsiadminiframe').style.zIndex < zindex - 1) {
                event.target.closest('.jitsiadminiframe').style.zIndex = zindex++;
            }
            const beforeTranslate = event.drag.beforeTranslate;

            frame.translate = beforeTranslate;
            event.target.style.width = `${event.width}px`;
            event.target.style.height = `${event.height}px`;
            event.target.style.transform = `translate(${beforeTranslate[0]}px, ${beforeTranslate[1]}px)`;
            event.target.style.removeProperty('border');
            event.target.querySelector('.headerBar').style.removeProperty('padding');

            event.target.dataset.x = beforeTranslate[0];
            event.target.dataset.y = beforeTranslate[1];
        }
    ).on("resizeEnd", ({target, isDrag, clientX, clientY}) => {
        // console.log("onResizeEnd", target, isDrag);
        dragactive = false;
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
    container.querySelector('iframe').style.height = '0px';
    container.dataset.beforeminwidth = container.style.width;
    container.classList.add('minified');
    container.querySelector('.headerBar').addEventListener('click', removeFromMinibar);
    //
    // setTimeout(function () {
    //     container.querySelector('.headerBar').addEventListener('click', (e) => {
    //         var ele = e.currentTarget.closest('.minified');
    //         removeFromMinibar(ele);
    //     }, {once: true})
    // }, 1);

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

    removeInteraction();
}


export {initStartIframe, createIframe}
