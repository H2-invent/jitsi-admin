/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');
import stc from'string-to-color/index';
import {notifymoderator,refresh} from './lobbyNotification'
import {initCircle} from './initCircle'
import {initWebcam} from './cameraUtils'
initWebcam()
const es = new EventSource(topic);

es.onmessage = e => {
    var data = JSON.parse(e.data)
    if(data.type === 'notification'){
        notifymoderator(data)
    }else if(data.type === 'refresh'){
        refresh(data)
    }

}
initCircle();


