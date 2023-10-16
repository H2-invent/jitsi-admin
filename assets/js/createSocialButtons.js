import {api} from "./jitsiUtils";

var gDevices;
export function initSocialIcons(api) {
    createSocialBox();
    createCameraChangeButton(api);
}

function createCameraChangeButton(api) {
    api.getAvailableDevices().then(devices => {
        if (devices['videoInput'].length > 1) {
            gDevices = devices;
            var $body = document.getElementById('wrapperIcons');
            var $object = "<div class='wrapper'><div id='social_changeCamera' class='conference-icon'><i class=\"fa-solid fa-camera-rotate\"></i></div></div> ";
            $body.insertAdjacentHTML("afterbegin", $object);
            var $toggle = document.getElementById('social_changeCamera');
            $toggle.addEventListener('click',function (){
                api.getCurrentDevices().then(devices => {
                    var $currentVideo = devices['videoInput']['label'];
                    var $avilableVideo = [];
                    gDevices['videoInput'].forEach(function (item){
                        $avilableVideo.push(item['label']);
                    });
                    const currentIndex = $avilableVideo.indexOf($currentVideo);
                    const nextIndex = (currentIndex + 1) % $avilableVideo.length;
                    var $newVideo = $avilableVideo[nextIndex];
                    api.setVideoInputDevice($newVideo);
                });
            })
        }
    })

}

function createSocialBox() {
    // var $body = document.getElementById('frame');
    // var $object = "<div id='social_icon_box' class='social_icon_box'></div>";
    // $body.insertAdjacentHTML("afterbegin", $object);
}