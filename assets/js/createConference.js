import interact from 'interactjs'

let counter = 50;
let zindex = 10
let width = window.innerWidth * 0.75;
let height = window.innerHeight * 0.75;

function initStartIframe() {
    var initIframe = document.querySelectorAll('.startIframe');
    for (var i = 0; i < initIframe.length; i++) {
        initIframe[i].addEventListener("click", function (e) {
                e.preventDefault();
                width = window.innerWidth * 0.75;
                height = window.innerHeight * 0.75;
                counter = (document.querySelectorAll('.jitsiadminiframe').length + 1) * 50;
                var random = Math.random() * 10000;
                var html =
                    '<div id="jitsiadminiframe' + random + '" class="jitsiadminiframe" data-x="' + counter + '" data-y="' + counter + '">' +
                    '<div class="headerBar">' +
                    '<div class="dragger actionIcon"><i class="fa-solid fa-arrows-up-down-left-right me-2"></i>' + this.dataset.roomname + '</div>' +
                    '<div class="actionIconLeft">' +
                    '<div class="minimize  actionIcon"><i class="fa-solid fa-window-minimize"></i></div> ' +
                    '<div class="button-maximize  actionIcon" data-maximal="0"><i class="fa-solid fa-window-maximize"></i></div> ' +
                    '<div class="closer  actionIcon"><i class="fa-solid fa-xmark"></i></div> ' +
                    '</div>' +
                    '</div>' +
                    '<iframe  ></iframe>' +
                    '</div> ';

                var site = this.href;
                document.getElementById('window').insertAdjacentHTML('beforeend', html);
                document.getElementById('jitsiadminiframe' + random).style.transform = 'translate(' + counter + 'px, ' + counter + 'px)';
                document.getElementById('jitsiadminiframe' + random).style.width = width + 'px';
                document.getElementById('jitsiadminiframe' + random).style.height = height + 'px';
                document.getElementById('jitsiadminiframe' + random).style.zIndex = zindex++;
                document.getElementById('jitsiadminiframe' + random).querySelector('iframe').src = site;
                document.getElementById('jitsiadminiframe' + random).querySelector('.button-maximize').dataset.maximal = "0";
                document.getElementById('jitsiadminiframe' + random).querySelector('.closer').dataset.id = 'jitsiadminiframe' + random;

                document.getElementById('jitsiadminiframe' + random).querySelector('.closer').addEventListener('click', function (e) {
                    var id = e.currentTarget.dataset.id;
                    sendCommand(id, {type: 'close'})

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
                        e.currentTarget.closest('.jitsiadminiframe').style.border = 'none'
                        e.currentTarget.closest('.jitsiadminiframe').querySelector('.headerBar').style.padding = '8px'
                        e.currentTarget.querySelector('i').classList.remove('fa-window-maximize');
                        e.currentTarget.querySelector('i').classList.add('fa-window-restore');
                        e.currentTarget.dataset.maximal = "1";
                    } else {
                        e.currentTarget.closest('.jitsiadminiframe').style.width = e.currentTarget.dataset.width;
                        e.currentTarget.closest('.jitsiadminiframe').style.height = e.currentTarget.dataset.height;
                        e.currentTarget.closest('.jitsiadminiframe').style.transform = e.currentTarget.dataset.translation;
                        e.currentTarget.closest('.jitsiadminiframe').style.removeProperty('border');
                        e.currentTarget.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
                        e.currentTarget.closest('.jitsiadminiframe').dataset.x = e.currentTarget.dataset.x;
                        e.currentTarget.closest('.jitsiadminiframe').dataset.y = e.currentTarget.dataset.y;
                        e.currentTarget.querySelector('i').classList.remove('fa-window-restore');
                        e.currentTarget.querySelector('i').classList.add('fa-window-maximize');
                        e.currentTarget.dataset.maximal = "0";
                    }
                })

                // document.getElementById('jitsiadminiframe' + random).querySelector('.button-minimize').addEventListener('click', function (e) {
                //
                // })

                document.getElementById('jitsiadminiframe' + random).addEventListener('click', function (e) {
                    if (event.currentTarget.style.zIndex < zindex - 1) {
                        event.currentTarget.style.zIndex = zindex++;
                    }
                })
                const position = {x: counter, y: counter}
                interact('.dragger').draggable({
                    listeners: {
                        start(event) {
                            position.x = parseInt(event.target.closest('.jitsiadminiframe').dataset.x)
                            position.y = parseInt(event.target.closest('.jitsiadminiframe').dataset.y)
                            event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').dataset.maximal = "0";
                            event.target.closest('.jitsiadminiframe').style.removeProperty('border');
                            event.target.closest('.jitsiadminiframe').querySelector('.headerBar').style.removeProperty('padding');
                            event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.remove('fa-window-restore');
                            event.target.closest('.jitsiadminiframe').querySelector('.button-maximize').querySelector('i').classList.add('fa-window-maximize');

                        },
                        move(event) {
                            if (event.target.closest('.jitsiadminiframe').style.zIndex < zindex - 1) {
                                event.target.closest('.jitsiadminiframe').style.zIndex = zindex++;
                            }
                            position.x += event.dx
                            position.y += event.dy
                            event.target.closest('.jitsiadminiframe').style.transform =
                                `translate(${position.x}px, ${position.y}px)`


                        },
                        end(event) {
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
                setTimeout(function () {
                    sendCommand('jitsiadminiframe' + random, {type: 'init'});
                }, 2000)
                counter += 40;
            }
        );

    }
    window.addEventListener('message', function (e) {
        // Get the sent data
        const data = e.data;

        // If you encode the message in JSON before sending them,
        // then decode here
        recievecommand(data)

    });
}

function sendCommand(id, message) {
    var ele = document.getElementById(id).querySelector('iframe');
    message.frameId = id;
    ele.contentWindow.postMessage(JSON.stringify(message), '*');
}

function recievecommand(data) {
    const decoded = JSON.parse(data);
    const type = decoded.type

    if (type === 'close') {
        closeIframe(decoded.frameId)
    }
}

function closeIframe(id) {
    document.getElementById(id).remove();
}

export {initStartIframe}
