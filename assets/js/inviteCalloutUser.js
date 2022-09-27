import $ from "jquery";
import {Dropdown} from "mdb-ui-kit";


let timer;              // Timer identifier
const waitTime = 500;   // Wait time in milliseconds
let userUidSelected = null;
let userNameShown = null;
var closeTimer = null;
export function initSearchCallOut() {

    var $searchUserField = document.getElementById('searchCallOutParticipants');
    if ($searchUserField !== null) {

        let trigger = document.getElementById('searchCallOutParticipantsDropdownTrigger')
        document.getElementById('searchCallOutParticipants').addEventListener("focus", (e) => {
            Dropdown.getOrCreateInstance(trigger).show()
            if (closeTimer){
               clearTimeout(closeTimer);
               closeTimer = null;
            }
        })
        document.getElementById('searchCallOutParticipants').addEventListener("blur", (e) => {
            closeTimer = setTimeout(function () {
                Dropdown.getOrCreateInstance(trigger).hide();
                closeTimer = null;
            }, 500);
        })

        $searchUserField.addEventListener("keyup", function (e) {
            var $ele = this;
            const $search = $ele.value;
            const $url = $ele.getAttribute("href") + '?search=' + $search;
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchUSer($url, $search);
            }, waitTime);
        })
    }
    document.getElementById('addCalloutUserBtn').addEventListener('click', function (ev) {
        sendInvitation(ev.currentTarget.getAttribute("href"));
    })
}

const searchUSer = ($url, $search) => {
    if ($search.length > 0) {
        $.getJSON($url, function (data) {
            var users = data.user
            var dropdown = document.getElementById('searchCallOutParticipantsDropdown');
            dropdown.innerHTML = '';
            for (let user of users) {
                console.log(user);
                var dropdownEle = '<a class="dropdown-item calloutSearchUser" data-name="' + user.nameNoIcon + '" data-val="' + user.id + '" href="#">' + user.name + '</a>';
                dropdown.insertAdjacentHTML('beforeend', dropdownEle);
            }
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
    userUidSelected = userEle.dataset.val;
    userNameShown = userEle.dataset.name;

    document.getElementById('searchCallOutParticipants').value = userNameShown;
    console.log(userUidSelected);
}

function sendInvitation(url) {

    $.ajax({
        type: "POST",
        url: url,
        data: {
            uid: userUidSelected,
        },

    }).done(function (data) {
        console.log(data);
    })
        .fail(function (data) {
            console.log(data);
        });

}
