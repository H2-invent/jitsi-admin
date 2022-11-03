import {setSnackbar} from './myToastr'
let text;
function initSendMessage() {
    document.addEventListener('click',function (e) {
        var ele = e.target.closest('.sendMessageToWaitingUserBtn');
        if(ele){
            var input = ele.closest('.input-group').querySelector('input');
            sendToServer(input.dataset.url,input.value,input.dataset.uid);
            input.value = '';
        }
    })
    document.addEventListener('click',function (e) {
        var ele = e.target.closest('.sendMessageToWaitingUserPredefined');
        if(ele){
            sendToServer(ele.dataset.url,parseInt(ele.dataset.id),ele.dataset.uid);
        }
    })
    document.addEventListener('keyup',function (e) {
        var ele = e.target.closest('.sendMessageToWaitingUserInput');
        if(ele){
            var text = ele.value;
            var btn = ele.closest('.input-group').querySelector('button');
            if (text.length > 0){
                btn.disabled = false;
                if (e.key === 'Enter' || e.keyCode === 13) {
                    var input = ele.closest('.input-group').querySelector('input');
                    sendToServer(input.dataset.url,input.value,input.dataset.uid);
                    input.value = '';
                }
            }else {
                btn.disabled = true;
            }
        }
    })




}

function sendToServer(url,message,uid) {
    fetch(url, {
        method: "POST",
        headers: {'Content-Type': 'application/json'},
        body: JSON.stringify({uid:uid,message:message})
    })
        .then((response) => response.json())
        .then((data) => {
            setSnackbar(data.message,data.error?'danger':'success',false,'0x00',5000);
        })

}
export {initSendMessage}