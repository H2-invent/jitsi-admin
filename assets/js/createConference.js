import interact from 'interactjs'
import {leaveMeeting} from "./websocket";
import md5 from "blueimp-md5"
import {setSnackbar} from "./myToastr";

let counter = 50;
let zindex = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;

function initStartIframe() {

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
        '<div class="dragger actionIcon"><i class="fa-solid fa-arrows-up-down-left-right me-2"></i>' + title + '</div>' +
        '<div class="actionIconLeft">' +
        '<div class="minimize  actionIcon"><i class="fa-solid fa-window-minimize"></i></div> ' +
        '<div class="button-maximize  actionIcon" data-maximal="0"><i class="fa-solid fa-window-maximize"></i></div> ' +
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
        if (closeIntelligent) {
            var id = e.currentTarget.dataset.id;
            sendCommand(id, {type: 'pleaseClose'})
        } else {
            closeIframe('jitsiadminiframe' + random);
        }
    })
    document.getElementById('jitsiadminiframe' + random).querySelector('.minimize').addEventListener('click', function (e) {
        minimizeFrame('jitsiadminiframe' + random)
    })

    document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').addEventListener('click', function (e) {
        if (e.currentTarget.dataset.maximal === "0") {
            e.currentTarget.dataset.height = e.currentTarget.closest('.jitsiadminiframe').style.height;
            e.currentTarget.dataset.width = e.currentTarget.closest('.jitsiadminiframe').style.width;
            e.currentTarget.dataset.translation = e.currentTarget.closest('.jitsiadminiframe').style.transform;
            e.currentTarget.dataset.x = e.currentTarget.closest('.jitsiadminiframe').dataset.x;
            e.currentTarget.dataset.y = e.currentTarget.closest('.jitsiadminiframe').dataset.y;
            e.currentTarget.closest('.jitsiadminiframe').style.width = "100%";
            e.currentTarget.closest('.jitsiadminiframe').style.height = "100vh";
            e.currentTarget.closest('.jitsiadminiframe').style.transform = 'translate(0px, 0px)'
            e.currentTarget.closest('.jitsiadminiframe').style.borderWidth = '0px'
            e.currentTarget.closest('.jitsiadminiframe').querySelector('.headerBar').style.padding = '8px'
            e.currentTarget.querySelector('i').classList.remove('fa-window-maximize');
            e.currentTarget.querySelector('i').classList.add('fa-window-restore');
            e.currentTarget.dataset.maximal = "1";
        } else {
            e.currentTarget.closest('.jitsiadminiframe').style.width = e.currentTarget.dataset.width;
            e.currentTarget.closest('.jitsiadminiframe').style.height = e.currentTarget.dataset.height;
            e.currentTarget.closest('.jitsiadminiframe').style.transform = e.currentTarget.dataset.translation;
            e.currentTarget.closest('.jitsiadminiframe').style.removeProperty('border-width');
            e.currentTarget.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
            e.currentTarget.closest('.jitsiadminiframe').dataset.x = e.currentTarget.dataset.x;
            e.currentTarget.closest('.jitsiadminiframe').dataset.y = e.currentTarget.dataset.y;
            e.currentTarget.querySelector('i').classList.remove('fa-window-restore');
            e.currentTarget.querySelector('i').classList.add('fa-window-maximize');
            e.currentTarget.dataset.maximal = "0";
        }
    })


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
    addInteractions();
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
}

function addInteractions() {
    const position = {x: counter, y: counter}
    interact('.dragger').draggable({
        listeners: {
            start(event) {
                if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
                    return null;
                }
                position.x = parseInt(event.target.closest('.jitsiadminiframe').dataset.x)
                position.y = parseInt(event.target.closest('.jitsiadminiframe').dataset.y)
                event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').dataset.maximal = "0";
                event.target.closest('.jitsiadminiframe').style.removeProperty('border');
                event.target.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
                event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.remove('fa-window-restore');
                event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.add('fa-window-maximize');

            },
            move(event) {
                if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
                    return null;
                }
                if (event.target.closest('.jitsiadminiframe').style.zIndex < zindex - 1) {
                    event.target.closest('.jitsiadminiframe').style.zIndex = zindex++;
                }
                position.x += event.dx
                position.y += event.dy
                event.target.closest('.jitsiadminiframe').style.transform =
                    `translate(${position.x}px, ${position.y}px)`


            },
            end(event) {
                if (event.target.closest('.jitsiadminiframe').classList.contains('minified')) {
                    return null;
                }
                event.target.closest('.jitsiadminiframe').dataset.x = position.x;
                event.target.closest('.jitsiadminiframe').dataset.y = position.y;

            }
        }
    })
    interact('.jitsiadminiframe')
        .resizable({
            edges: {top: true, left: true, bottom: true, right: true},
            listeners: {
                move: function (event) {
                    if (event.target.classList.contains('minified')) {
                        return null;
                    }
                    if (event.target.style.zIndex < zindex - 1) {
                        event.target.style.zIndex = zindex++;

                    }
                    let {x, y} = event.target.dataset

                    x = (parseFloat(x) || 0) + event.deltaRect.left
                    y = (parseFloat(y) || 0) + event.deltaRect.top

                    Object.assign(event.target.style, {
                        width: `${event.rect.width}px`,
                        height: `${event.rect.height}px`,
                        transform: `translate(${x}px, ${y}px)`
                    })
                    event.target.style.removeProperty('border');
                    event.target.querySelector('.headerBar').style.removeProperty('padding');
                    Object.assign(event.target.dataset, {x, y})
                }
            }
        })
}

function minimizeFrame(id) {
    var container = document.getElementById(id);
    moveToMinibar(container);
}

function moveToMinibar(container) {
    // container.dataset.parent = container.parentNode.id;
    // var minimizeBar = document.getElementById('minimizeBar');
    // minimizeBar.append(container);
    container.querySelector('iframe').style.height = '0px';
    container.dataset.beforeminwidth = container.style.width;
    container.classList.add('minified');
    setTimeout(function () {
        container.querySelector('.headerBar').addEventListener('click', (e) => {
            var ele = e.currentTarget.closest('.minified');
            removeFromMinibar(ele);
        }, {once: true})
    }, 1);

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

function removeFromMinibar(container) {
    if (container.classList.contains('minified')) {
        container.classList.remove('minified');
        container.style.width = container.dataset.beforeminwidth;
        container.style.removeProperty('left');
        setWidthOfminified();
    }
}


export {initStartIframe, createIframe}
