import ZingTouch from 'zingtouch';

function initDragParticipants() {
    var activeRegion = new ZingTouch.Region(document.getElementById('waitingUserWrapper'),null,false);
    let childElement = document.querySelectorAll('.waitingUserCard ');
    childElement.forEach(function (e) {
        activeRegion.bind(e, new ZingTouch.Pan({
            threshold: 0
        }), function (event) {
            var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
            var x = event.detail.data[0]['distanceFromOrigin'] * Math.cos(rad)
            console.log(x);
            event.target.querySelector('.card').style.transform = "translate("+x+"px,0)";
            // if (x < 0) {
            //     document.getElementById('sliderTop').style.top = 0;
            // } else if (x > 20) {
            //     var top = -1 * document.getElementById('col-waitinglist').clientHeight + 'px';
            //     var ele = document.getElementById('sliderTop');
            //     ele.style.top = top;
            //     document.getElementById('dragger').classList.remove('active');
            //
            // }
        });
    })


}

export {initDragParticipants}