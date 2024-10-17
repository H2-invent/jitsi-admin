

var gDevices;
export function initSocialIcons(cbFkt) {
    createSocialBox();
    createCameraChangeButton(cbFkt);
}

function createCameraChangeButton(cbFkt) {
    navigator.mediaDevices.enumerateDevices().then(devices => {
        if (document.getElementById('social_changeCamera')) {
            document.getElementById('social_changeCamera').remove();
        }

        const videoInputDevices = devices.filter(device => device.kind === 'videoinput');

        if (videoInputDevices.length > 1) {
            var $body = document.getElementById('wrapperIcons');
            var $object = "<div class='wrapper'><div id='social_changeCamera' class='conference-icon'><i class=\"fa-solid fa-camera-rotate\"></i></div></div>";
            $body.insertAdjacentHTML("afterbegin", $object);
            var $toggle = document.getElementById('social_changeCamera');

            let currentCameraIndex = 0;

            $toggle.addEventListener('click', function () {
                currentCameraIndex = (currentCameraIndex + 1) % videoInputDevices.length;
                const newVideoDevice = videoInputDevices[currentCameraIndex];

                // Call the cbFkt with the new video device ID
                cbFkt.bind(this)(newVideoDevice.label);
            });
        }
    }).catch(error => {
        console.error('Error accessing media devices:', error);
    });
}
function createSocialBox() {
    // var $body = document.getElementById('frame');
    // var $object = "<div id='social_icon_box' class='social_icon_box'></div>";
    // $body.insertAdjacentHTML("afterbegin", $object);
}