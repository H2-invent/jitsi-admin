/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');

var video = document.querySelector("#testAudio");
var echoTest = 0;
var audio = [];
var mic = [];
var audioId = null;
var micId= null;

function initAUdio() {
    navigator.mediaDevices.enumerateDevices().then(function (devices) {

        devices.forEach(function (device) {
            if (device.kind === 'audiooutput') {
                $('#audioOutputSelect').append(
                    '<a class="dropdown-item audio_outputSelect" data-value="' + device.deviceId + '">' + device.label + '</a>'
                )
                audio.push(device);
            }
            if (device.kind === 'audioinput') {
                $('#audioInputSelect').append(
                    '<a class="dropdown-item audio_inputSelect" data-value="' + device.deviceId + '">' + device.label + '</a>'
                )
                mic.push(device);
            }
        });
        console.log(mic);
        console.log(audio);
        $('.audio_inputSelect').click(function () {
            $('.audio_inputSelect').removeClass('selectedDevice');
            $(this).addClass('selectedDevice');
            audioId = $(this).data('value');
        })
        $('.audio_outputSelect').click(function () {
            $('.audio_outputSelect').removeClass('selectedDevice');
            $(this).addClass('selectedDevice');
            micId = $(this).data('value');
        })
        audioId = audio[0].deviceId;
        console.log(audioId);
        $('.audio_outputSelect[data-value="'+audioId+'"]').addClass('selectedDevice');
        micId = mic[0].deviceId;
        $('.audio_inputSelect[data-value="'+micId+'"]').addClass('selectedDevice');
    })

    $('#startEcho').click(function (e) {
        e.preventDefault();
        toggleEcho();
    })
}


function toggleEcho(){
    const audioCtx = new AudioContext();
    if (navigator.mediaDevices.getUserMedia) {
        var constraints = {
            'audio': {'echoCancellation': true,deviceId: micId},
            'video': false,
        };
        navigator.mediaDevices.getUserMedia({audio: constraints})
            .then(function (stream) {
                const microphone = audioCtx.createMediaStreamSource(stream)
                var destination = audioCtx.createMediaStreamDestination({audio: {deviceId: {excat: audioId}}});
                new Audio().srcObject = srcStream;
                microphone.conntect(destination);
            })
            .catch(function (err0r) {
                console.log(err0r);
                console.log("Something went wrong!");
            });
    }
}
export {initAUdio,micId, audioId}