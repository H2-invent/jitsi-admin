/*
 * Welcome to your app's main JavaScript file!
 *
 */

import $ from 'jquery';

import('bootstrap');
import('popper.js');
global.$ = global.jQuery = $;
import('mdbootstrap');

var video = document.querySelector("#lobbyWebcam");
var toggle = 0;
var webcams = [];
var choosenId= null;

function initWebcam() {
    navigator.mediaDevices.enumerateDevices().then(function (devices) {
        devices.forEach(function (device) {
            if (device.kind === 'videoinput') {
                webcams[device.label] = device.deviceId
                $('#webcamSelect').append(
                    '<li><a class="dropdown-item webcamSelect" data-value="' + device.deviceId + '">' + device.label + '</a></li>'
                )
                webcams.push(device);
            }
        });
        $('.webcamSelect').click(function () {
            setButtonName($('#selectWebcamDropdown'), $(this).text());
            choosenId = $(this).data('value');
            startWebcam(choosenId);
        })
        choosenId = webcams[0].deviceId;
        setButtonName($('#selectWebcamDropdown'), webcams[0].label);
        startWebcam(choosenId);
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
        navigator.mediaDevices.getUserMedia({video: constraints,audio:false})
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
export {initWebcam,choosenId}