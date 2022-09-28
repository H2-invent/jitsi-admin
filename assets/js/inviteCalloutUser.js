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

export function initSearchCallOut() {

    if (searchUserInput !== null) {

        let trigger = document.getElementById('searchCallOutParticipantsDropdownTrigger')
        searchUserInput.addEventListener("focus", (e) => {
            if (document.getElementById('sliderTop')) {
                document.getElementById('sliderTop').classList.add('openSlider');
            }
            Dropdown.getOrCreateInstance(trigger).show()
            if (closeTimer) {
                clearTimeout(closeTimer);
                closeTimer = null;
            }
        })
        searchUserInput.addEventListener("blur", (e) => {
            closeTimer = setTimeout(function () {
                if (document.getElementById('sliderTop')) {
                    document.getElementById('sliderTop').classList.remove('openSlider');
                }

                Dropdown.getOrCreateInstance(trigger).hide();
                closeTimer = null;
            }, 500);
        })

        searchUserInput.addEventListener("keyup", function (e) {
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
        ev.currentTarget.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
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
                var dropdownEle =
                    '<a id="user_' + user.uid
                    + '" class="d-flex align-items-center dropdown-item calloutSearchUser" data-name="'
                    + user.nameNoIcon
                    + '" data-val="'
                    + user.id
                    + '" href="#">' +
                    '<div class="dot"></div>'
                    + user.name
                    + '</a>';
                dropdown.insertAdjacentHTML('beforeend', dropdownEle);
                usersArr.push(user.uid);
            }
            socket.on('giveOnlineStatus', function (data) {
                data = JSON.parse(data);
                for (var d in data) {
                    document.getElementById('user_' + d).dataset.status = data[d];
                }
            })
            socket.emit('giveOnlineStatus', JSON.stringify(usersArr));
            var ele = document.querySelectorAll('.calloutSearchUser');
            for (var e of ele) {
                e.addEventListener('click', function (ev) {
                    selectUser(ev.target);
                })
            }

        })
    }
}

function selectUser(userEle) {
    inviteButton.disabled = false;
    userUidSelected = userEle.dataset.val;
    userNameShown = userEle.dataset.name;
    searchUserInput.value = userNameShown;
    initInput();
}

function sendInvitation(url) {

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
        initInput();
    })
        .fail(function (data) {
        });

}

function initInput() {
    document.querySelectorAll('.form-outline').forEach((formOutline) => {
        new Input(formOutline).update();
    });
}