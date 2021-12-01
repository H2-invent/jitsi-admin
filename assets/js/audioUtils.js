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
                    '<li><a class="dropdown-item audio_inputSelect" data-value="' + device.deviceId + '">' + device.label + '</a></li>'
                )
                audio.push(device);
            }
            if (device.kind === 'audioinput') {
                $('#audioInputSelect').append(
                    '<li><a class="dropdown-item audio_outputSelect" data-value="' + device.deviceId + '">' + device.label + '</a></li>'
                )
                mic.push(device);
            }
        });
        console.log(mic);
        console.log(audio);
        $('.audio_outputSelect').click(function () {
            setButtonName($('#audioInputSelect'), $(this).text());
            audioId = $(this).data('value');
        })
        audioId = audio[0].deviceId;
        setButtonName($('#audioInputSelect'), audio[0].label);
    })

    $('#webcamSwitch').click(function (e) {
        e.preventDefault();
        toggleWebcam(e);
    })
}
function toggleWebcam(e){
    if(toggle === 1){
        stopWebcam();
    }else {
       startWebcam(choosenId);
    }
}

function startWebcam(id){
    if (navigator.mediaDevices.getUserMedia) {
        var constraints = { deviceId: { exact: id } };
        navigator.mediaDevices.getUserMedia({video: constraints})
            .then(function (stream) {
                video.srcObject = stream;
                toggle = 1;
                video.style.height ='auto';
                $('#webcamSwitch').removeClass('fa-video').addClass('fa-video-slash')
            })
            .catch(function (err0r) {
                console.log("Something went wrong!");
            });
    }
}

function stopWebcam() {
    var stream = video.srcObject;
    var tracks = stream.getTracks();
    var $heigth = video.clientHeight;
    video.style.height = $heigth+'px';
    for (var i = 0; i < tracks.length; i++) {
        var track = tracks[i];
        track.stop();
        $('#webcamSwitch').addClass('fa-video').removeClass('fa-video-slash')
        toggle = 0;
    }
    video.srcObject = null;
}
function setButtonName(button, text) {
    button.text(text);
}
export {initAUdio,micId, audioId}