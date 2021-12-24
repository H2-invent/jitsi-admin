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
        $('.audio_inputSelect').click(function () {
            $('.audio_inputSelect').removeClass('selectedDevice');
            $(this).addClass('selectedDevice');
            micId = $(this).data('value');
        })
        $('.audio_outputSelect').click(function () {
            $('.audio_outputSelect').removeClass('selectedDevice');
            $(this).addClass('selectedDevice');
            audioId = $(this).data('value');
        })
        audioId = audio[0].deviceId;
        console.log(audioId);
        $('.audio_outputSelect[data-value="' + audioId + '"]').addClass('selectedDevice');
        micId = mic[0].deviceId;
        $('.audio_inputSelect[data-value="' + micId + '"]').addClass('selectedDevice');
    })

    $('#startEcho').click(function (e) {
        var text;
        if (echoTest === 0) {
            echoTest = 1;
            text = $(this).data('textoff');
            $(this).text(text);
        } else {
            echoTest = 0;
        }
        e.preventDefault();
        toggleEcho();
    })
}


function toggleEcho() {
    audioCtx = new AudioContext({
        latencyHint: 'interactive',
        sampleRate: 44100
    });
    if (navigator.mediaDevices.getUserMedia) {
        var constraints = {'echoCancellation': true, deviceId: {exact: micId}};
        navigator.mediaDevices.getUserMedia({audio: constraints, video: false})
            .then(function (stream) {

                var source = audioCtx.createMediaStreamSource(stream)
                var delay = audioCtx.createDelay(2.0);
                var gain = audioCtx.createGain(10);
                var destination = audioCtx.createMediaStreamDestination({audio: {deviceId: {excat: audioId}}});
                source.connect(delay);
                delay.connect(gain);
                if (echoTest === 1) {
                    gain.connect(audioCtx.destination);
                } else {
                    source.audioCtx.close().then(function () {
                        console.log('1.2');
                        $('#startEcho').text($(this).data('textOn'));
                    });
                }
            })
            .catch(function (err0r) {
                console.log(err0r);
                console.log("Something went wrong!");
            });
    }
}

export {initAUdio, micId, audioId}