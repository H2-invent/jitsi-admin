import $ from "jquery";
import {Dropdown, Input} from "mdb-ui-kit";
import {socket} from './websocket'

let timer;              // Timer identifier
const waitTime = 500;   // Wait time in milliseconds
let userUidSelected = null;
let userNameShown = null;
var closeTimer = null;
const inviteButton = document.getElementById('addCalloutUserBtn');
const searchUserInput = document.getElementById('searchCallOutParticipants');
const dropdown = document.getElementById('searchCallOutParticipantsDropdown');
const trigger = document.getElementById('searchCallOutParticipantsDropdownTrigger');

export function initSearchCallOut() {

    if (searchUserInput !== null) {


        searchUserInput.addEventListener("focus", (e) => {
            if (document.getElementById('sliderTop')) {
                document.getElementById('sliderTop').classList.add('openSlider');
            }
            Dropdown.getOrCreateInstance(trigger).show()

            document.addEventListener('mousedown', clickOutsideDrodown);
        })

        searchUserInput.addEventListener("click", (e) => {
            Dropdown.getOrCreateInstance(trigger).show();
        })

        function clickOutsideDrodown(e) {

            if (e.target.closest('.dropdown-menu') === dropdown) {
                e.stopPropagation();
                e.preventDefault();
                e.stopImmediatePropagation();
            }
        }

        searchUserInput.addEventListener("blur", (e) => {
            document.removeEventListener('mousedown', clickOutsideDrodown);
            Dropdown.getOrCreateInstance(trigger).hide();
            if (document.getElementById('sliderTop')) {
                document.getElementById('sliderTop').classList.remove('openSlider');
            }

        })

        searchUserInput.addEventListener("keyup", function (e) {

            if (e.key === 'Enter') {
                Dropdown.getOrCreateInstance(trigger).hide();

                sendInvitation(inviteButton.getAttribute('href'));
                return
            }
            if (e.key === 'ArrowDown') {
                selectNext();
                return;
            }
            if (e.key === 'ArrowUp') {
                selectPrev();
                return;
            }

            inviteButton.disabled = true;
            var $ele = this;
            const $search = $ele.value;
            const $url = $ele.getAttribute("href") + '?search=' + $search;
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchUSer($url, $search);
            }, waitTime);
        })
    }
    inviteButton.addEventListener('click', function (ev) {
        sendInvitation(ev.currentTarget.getAttribute("href"));
    })
}

const searchUSer = ($url, $search) => {

    if ($search.length > 0) {
        $.getJSON($url, function (data) {
            var users = data.user
            var usersArr = [];

            dropdown.innerHTML = '';
            for (let user of users) {
                let ele = document.createElement('a');
                if (typeof user.uid !== 'undefined') {
                    ele.id = "user_" + user.uid;
                }
                ele.classList.add('d-flex');
                ele.classList.add('align-items-center');
                ele.classList.add('dropdown-item');
                ele.classList.add('calloutSearchUser');
                ele.dataset.name = user.nameNoIcon;
                ele.dataset.val = user.id;
                let dot = document.createElement('div');
                dot.classList.add('dot');
                ele.appendChild(dot);
                let name = document.createElement('span');
                name.innerHTML = user.name
                ele.appendChild(name);
                ele.addEventListener('click', function (ele) {
                    selectUser(ele.currentTarget);
                    Dropdown.getOrCreateInstance(trigger).hide();
                })
                dropdown.appendChild(ele);
                if (typeof user.uid !== 'undefined') {
                    usersArr.push(user.uid);
                }

            }
            socket.on('giveOnlineStatus', function (data) {
                data = JSON.parse(data);
                for (var d in data) {
                    document.getElementById('user_' + d).dataset.status = data[d];
                }
            })
            socket.emit('giveOnlineStatus', JSON.stringify(usersArr));
            var ele = document.querySelectorAll('.calloutSearchUser');

        })
    }
}

function selectUser(ele) {

    inviteButton.disabled = false;
    userUidSelected = ele.dataset.val;
    userNameShown = ele.dataset.name;
    searchUserInput.value = userNameShown;
    // initInput();
}

function sendInvitation(url) {
    inviteButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
    inviteButton.disabled = true;
    $.ajax({
        type: "POST",
        url: url,
        data: {
            uid: userUidSelected,
        },

    }).done(function (data) {
        inviteButton.disabled = true;
        searchUserInput.value = '';
        inviteButton.innerHTML = '<i class="fa fa-user-plus"></i>';
    })
        .fail(function (data) {
        });

}

function initInput() {
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new Input(formOutline).update();
    });
}

function selectNext() {
    let activeElement = document.querySelector('.calloutSearchUser.active');
    let searchUserList = document.querySelectorAll('.calloutSearchUser')
    let newElement = null;
    if (!activeElement) {
        if (searchUserList.length > 0) {
            newElement = searchUserList[0];
        }
    } else {
        newElement = activeElement.nextElementSibling;
    }
    if (!newElement){
        newElement = searchUserList[0];
    }
    setNewActive(newElement, activeElement);
    selectUser(newElement)
    focusActive(dropdown, newElement)
}

function selectPrev() {
    let activeElement = document.querySelector('.calloutSearchUser.active');
    let searchUserList = document.querySelectorAll('.calloutSearchUser')
    let newElement = null;
    if (!activeElement) {
        if (searchUserList.length > 0) {
            newElement = searchUserList[searchUserList.length-1];
        }
    } else {
        newElement = activeElement.previousElementSibling;
    }
    if (!newElement){
        newElement = searchUserList[searchUserList.length-1];
    }
    setNewActive(newElement, activeElement);
    selectUser(newElement)
    focusActive(dropdown, newElement)
}

function setNewActive(newElement, activeElement) {
    if (newElement) {
        newElement.classList.add('active');
    }
    if (activeElement) {
        activeElement.classList.remove('active');
    }
}

function setnameInSearchField(element) {

}

function focusActive(parent, element) {
    parent.scrollTo({
        top: element.offsetTop,
        behavior: "smooth"
    });
}