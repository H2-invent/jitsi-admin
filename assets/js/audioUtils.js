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
var micId = null;
var AudioContext = window.AudioContext || window.webkitAudioContext;
var audioCtx;
var source;
var delay;
var gain;
var destination;
var gStream = null;
async function initAUdio() {
    try {
        await navigator.mediaDevices.getUserMedia({audio: true, video: false});


    navigator.mediaDevices.enumerateDevices().then(function (devices) {

        devices.forEach(function (device) {

            if (device.kind === 'audioinput') {
                var name = device.label.replace(/\(\w*:.*\)/g, "");
                $('#audioInputSelect').append(
                    '<a class="dropdown-item audio_inputSelect" href="#" data-value="' + device.deviceId + '">' + name + '</a>'
                )
                mic.push(device);
            }
        });
        $('.audio_inputSelect').click(function () {
            $('.audio_inputSelect').removeClass('selectedDevice');
            $(this).addClass('selectedDevice');
            micId = $(this).data('value');
        })

        micId = mic[0].deviceId;
        $('.audio_inputSelect[data-value="' + micId + '"]').addClass('selectedDevice');
    })
    }catch (e) {
        console.log(e);
    }

    $('#startEcho').click(function (e) {
        var text;
        if (echoTest === 0) {
            echoTest = 1;
            switchEchoOn();
        } else {
            echoTest = 0;
            switchEchoOff();
        }
        e.preventDefault();
    })
}

function echoOff() {
    if (echoTest === 1) {
        echoTest = 0;
        switchEchoOff();
    }
}

function switchEchoOn() {
    audioCtx = new AudioContext({
        latencyHint: 'interactive',

    });
    if (navigator.mediaDevices.getUserMedia) {
        var constraints = {'echoCancellation': false, deviceId: {exact: micId}};
        if (gStream !== null ){
            for (var i = 0 ;i < gStream.getTracks().length;i++){
                gStream.getTracks()[i].stop();
            }
        }
        navigator.mediaDevices.getUserMedia({audio: constraints, video: false})
            .then(function (stream) {
                const audio = document.createElement('audio');

                gStream = stream;
                source = audioCtx.createMediaStreamSource(stream)
                delay = audioCtx.createDelay(2.0);
                gain = audioCtx.createGain(10);
                destination = audioCtx.createMediaStreamDestination({audio: {deviceId: {excat: audioId}}});
                source.connect(delay);
                delay.connect(gain);
                gain.connect(audioCtx.destination);
                $('#startEcho').text($('#startEcho').data('textoff'));
            }).catch(function (err0r) {
            console.log(err0r);
            console.log("Something went wrong!");
        });
    }
}

function switchEchoOff() {
    audioCtx.close().then(function () {
        $('#startEcho').text($('#startEcho').data('texton'));
            for (var i = 0 ;i < gStream.getTracks().length;i++){
                gStream.getTracks()[i].stop();
            }

    });
}

export {initAUdio, micId, audioId, echoOff}