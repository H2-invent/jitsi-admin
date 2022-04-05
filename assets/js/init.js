import $ from 'jquery';

global.$ = global.jQuery = $;
import Push from "push.js";
import {initDarkmodeSwitch} from './switchDarkmode'

function initGenerell() {
    Push.Permission.request();
    if (typeof urlNotification !== 'undefined') {
        var myVar = setInterval(function () {
            $.getJSON(urlNotification, function (data) {
                for (var i = 0; i < data.length; i++) {
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
            })
        }, 20000);
    }
    initDarkmodeSwitch();
}


export {initGenerell}