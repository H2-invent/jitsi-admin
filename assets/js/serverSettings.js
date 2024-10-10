
import * as mdb from 'mdb-ui-kit'; // lib
export function showAppIdSettings() {
    document.getElementById('jwtServer').addEventListener('change',(e)=>{
        const element = document.getElementById('appId')
        const instance = new mdb.Collapse(element)
        if (e.target.checked) {
            instance.show();
        } else {
            instance.hide();
        }
    })
}
export function showLiveKitServerSettings(){

    document.getElementById('server_liveKitServer').addEventListener('change', function () {
        if (document.getElementById('server_liveKitServer').checked) {
            document.getElementById('jitsiMeetSettings').classList.add('d-none');
            document.getElementById('liveKitServerSettings').classList.remove('d-none');
        } else {
            document.getElementById('jitsiMeetSettings').classList.remove('d-none');
            document.getElementById('liveKitServerSettings').classList.add('d-none');
        }
    });
}

