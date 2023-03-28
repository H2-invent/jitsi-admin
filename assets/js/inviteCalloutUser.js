import $ from "jquery";
import {Dropdown, Input} from "mdb-ui-kit";
import {socket} from './websocket'

let timer;              // Timer identifier
const waitTime = 200;   // Wait time in milliseconds
let userUidSelected = null;
let userNameShown = null;
var closeTimer = null;
const inviteButton = document.getElementById('addCalloutUserBtn');
const searchUserInput = document.getElementById('searchCallOutParticipants');
const dropdown = document.getElementById('searchCallOutParticipantsDropdown');
const trigger = document.getElementById('searchCallOutParticipantsDropdownTrigger')
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
            document.getElementById('sliderTop').classList.remove('openSlider');
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
                    '<a ' + (typeof user.uid !== 'undefined' ? ('id="user_' + user.uid + '"') : '')
                    + ' class="d-flex align-items-center dropdown-item calloutSearchUser" data-name="'
                    + user.nameNoIcon
                    + '" data-val="'
                    + user.id
                    + '" href="#">' +
                    '<div class="dot"></div>'
                    + user.name
                    + '</a>';
                dropdown.insertAdjacentHTML('beforeend', dropdownEle);
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
            for (var e of ele) {
                e.addEventListener('click', function (ev) {
                    selectUser(ev.target);
                    Dropdown.getOrCreateInstance(trigger).hide();
                })
            }

        })
    }
}

function selectUser(userEle) {
    var ele = userEle.closest('.calloutSearchUser');
    inviteButton.disabled = false;
    userUidSelected = ele.dataset.val;
    userNameShown = ele.dataset.name;
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