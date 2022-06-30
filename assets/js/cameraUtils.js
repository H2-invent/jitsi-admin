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

async function initWebcam() {
    try {

        await navigator.mediaDevices.getUserMedia({audio: false, video: true});
        navigator.mediaDevices.enumerateDevices().then(function (devices) {

            devices.forEach(function (device) {
                if (device.kind === 'videoinput') {
                    webcams[device.label] = device.deviceId

                    var name = device.label.replace(/\(\w*:.*\)/g, "");
                    $('#webcamSelect').append(
                        '<li><a class="dropdown-item webcamSelect" href="#" data-value="' + device.deviceId + '">' + name + '</a></li>'
                    )
                    webcams.push(device);
                }
            });
            $('.webcamSelect').click(function () {
                stopWebcam();
                setButtonName($('#selectWebcamDropdown').find('span'), $(this).text());
                choosenId = $(this).data('value');
                startWebcam(choosenId);
            })
            choosenId = webcams[0].deviceId;
            var name = webcams[0].label.replace(/\(.*:.*\)/g, "");
            setButtonName($('#selectWebcamDropdown').find('span'), name);
            startWebcam(choosenId);
        })
    }catch (e) {
        console.log(e)
    }
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
                console.log(err0r);
                console.log("Something went wrong!");
            });
    }
}

function stopWebcam() {
        var stream = video.srcObject;
        if(typeof stream !== 'undefined' && stream !== null) {
        var tracks = stream.getTracks();
        var $heigth = video.clientHeight;
        video.style.height = $heigth + 'px';
        for (var i = 0; i < tracks.length; i++) {
            var track = tracks[i];
            track.stop();
            $('#webcamSwitch').addClass('fa-video').removeClass('fa-video-slash')
            toggle = 0;
        }
        video.srcObject = null;
    }
}
function setButtonName(button, text) {

    button.html(text);
}
export {initWebcam,choosenId,stopWebcam}