import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'
import {setSnackbar} from './myToastr'
function initGenerell() {
    Push.Permission.request();
    if (typeof urlNotification !== 'undefined') {
        var myVar = setInterval(function () {
            $.getJSON(urlNotification, function (data) {
                for (var i = 0; i < data.length; i++) {
                    if (document.visibilityState === 'hidden') {
                        Push.create(data[i].title, {
                            body: data[i].text,
                            icon: '/favicon.ico',
                            link: data[i].url,
                            onClick: function (ele) {

                                window.focus();
                                this.close();
                            }
                        });
                    }
                    setSnackbar(data[i].text,'info');
                }
            })
        }, 20000);
    }
    initDarkmodeSwitch();
}


export {initGenerell}