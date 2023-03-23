import {sendViaWebsocket} from "./websocket";

let timer;              // Timer identifier
const waitTime = 500;   // Wait time in milliseconds

// Search function
const sendAwayTime = (time) => {
    sendViaWebsocket('setAwayTime',time);
};

// Listen for `keyup` event


export function initAwayTime() {
    const input = document.querySelector('#awayTimeField');
    if (input){
        input.addEventListener('change', (e) => {
            const text = e.currentTarget.value;

            // Clear timer
            clearTimeout(timer);

            // Wait for X ms and then process the request
            timer = setTimeout(() => {
                sendAwayTime(text);
            }, waitTime);
        });
    }
}
export function setAwayTimeField(time) {
    var ele = document.getElementById('awayTimeField')
    if(ele){
    ele.value = time;
    }

}