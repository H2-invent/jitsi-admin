import $ from "jquery";
import autosize from "autosize";
import {Collapse, initMDB ,Dropdown,Input, Tooltip} from 'mdb-ui-kit';
import {initDropdown, initTooltip, reloadPartial} from "./confirmation";
import {setSnackbar} from "./myToastr"; // lib



let timer;              // Timer identifier
const waitTime = 500;   // Wait time in milliseconds

function initSearchUser() {
    initMDB({ Collapse,Input, Dropdown });



    const $searchUserField = document.getElementById('searchUser');
    const trigger = document.getElementById('searchUserDropdownTrigger');

    if ($searchUserField !== null && trigger !== null) {
        // Öffne das Dropdown beim Fokus auf das Suchfeld
        $searchUserField.addEventListener("focus", () => {
            Dropdown.getOrCreateInstance(trigger).show();
        });

        // Schließe das Dropdown beim Verlassen des Suchfeldes
        $searchUserField.addEventListener("blur", () => {
            // Verzögerung hinzufügen, damit Klick auf Dropdown-Element verarbeitet wird
            setTimeout(() => {
                Dropdown.getOrCreateInstance(trigger).hide();
            }, 150); // Ein kleines Timeout von 150 ms hilft, Klicks zu erfassen
        })

        autosize($('#new_member_member'));

        $('.defaultSearch').mousedown(function (e) {
            e.preventDefault();
            e.stopPropagation();
        })

        $('#searchUser').keyup(function (e) {
            var $ele = $(this);
            const $search = $ele.val();
            const $url = $ele.attr('data-search-url') + '?search=' + $search;
            clearTimeout(timer);
            timer = setTimeout(() => {
                searchUSer($url,$search);
            }, waitTime);

        })
    }
}
const searchUSer = ($url, $search) => {
    const apiUrl = document.getElementById('searchUser').dataset.targetUrl;

    if ($search.length > 0) {
        // Hole die Daten mit einem GET-Request
        fetch($url)
            .then(response => response.json())
            .then(data => {
                const $target = document.getElementById('participantUser');
                $target.innerHTML = ''; // Leere den Zielbereich

                const $user = data.user;
                if ($user.length > 0) {
                    $target.insertAdjacentHTML('beforeend', '<b><i class="fa fa-user"></i> </b>');
                }

                // Füge Benutzer hinzu
                for (let i = 0; i < $user.length; i++) {
                    // Erstelle das <a>-Element für Benutzer
                    const userElement = document.createElement('a');
                    userElement.className = "dropdown-item chooseParticipant addParticipants";
                    userElement.setAttribute('data-val', JSON.stringify([$user[i].id]));
                    userElement.href = "#";
                    userElement.innerHTML = `${$user[i].name}`; // Benutzername

                    $target.appendChild(userElement);

                    // Event-Listener für Klicks auf Benutzer
                    userElement.addEventListener('click', function(event) {
                        event.preventDefault(); // Verhindert das Standardverhalten des Links
                        const dataVal = JSON.parse(userElement.getAttribute('data-val'));
                        sendData(apiUrl, dataVal); // Sendet die Daten für Benutzer
                        event.currentTarget.remove();
                        document.getElementById('searchUser').value = '';
                    });
                }
                const $group = data.group;
                if ($group.length > 0) {
                    $target.insertAdjacentHTML('beforeend', '<b><i class="fa fa-users"></i><b>');
                }

                // Füge Gruppen hinzu
                for (let i = 0; i < $group.length; i++) {
                    // Erstelle das <a>-Element als HTML-Objekt
                    const linkElement = document.createElement('a');
                    linkElement.className = "dropdown-item chooseParticipant addParticipants";
                    linkElement.setAttribute('data-val', JSON.stringify($group[i].user));
                    linkElement.href = "#";
                    linkElement.innerHTML = `<span><i class="fas fa-users"></i>${$group[i].name}</span>`;

                    $target.appendChild(linkElement);

                    // Event-Listener für Klicks auf das Element (Gruppen)
                    linkElement.addEventListener('click', function(event) {
                        event.preventDefault(); // Verhindert das Standardverhalten des Links
                        const dataVal = JSON.parse(linkElement.getAttribute('data-val'));
                        sendData(apiUrl, dataVal); // Sendet die Daten für Gruppen
                        event.currentTarget.remove();
                        document.getElementById('searchUser').value = '';
                    });
                }

                // Füge Event-Listener für Kontakte hinzu


                // Entferne alle Tooltips, die möglicherweise vorher angezeigt wurden
                const tooltips = document.querySelectorAll('.tooltip');
                tooltips.forEach(tooltip => tooltip.remove());
                initMDB({ Tooltip });
            })
            .catch(error => {
                console.error('Fehler beim Abrufen der Daten:', error);
            });
    }
};

// Funktion zum Senden von Daten an die API
const sendData = (url, data) => {
    fetch(url, {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({ participant: data }) // Sende die `data-val`-Daten im Body
    })
        .then(response => response.json())
        .then(result => {
            const $searchUserField = document.getElementById('searchUser');
            console.log('Erfolgreiche Antwort von der API:', result);

            // Hole die reload-URL aus dem data-reload-url-Attribut
            const reloadUrl = $searchUserField.getAttribute('data-reload-url');

            reloadPartList(reloadUrl);
            if (result['validMember']){
                for (const email of result['validMember']){
                    setSnackbar(email,'','success',false,',10000');
                }
            }
        })
        .catch(error => {
            console.error('Fehler beim Senden der Daten:', error);
        });
};

const reloadPartList = (url)=>{
    if (url) {
        // Lade die aktualisierte Teilnehmerliste
        reloadPartial(url,'#atendeeList');
    } else {
        console.error('Keine reload-URL gefunden');
    }
}
const removeElement = ($ele) => {
    var el = $ele.closest('.addParticipants')
    el.classList.add('willRemoved');
    setTimeout(function () {
        el.remove();
    },900);
}

export {initSearchUser};
