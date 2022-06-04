import ZingTouch from 'zingtouch';

function initDragDragger() {
    var activeRegion = new ZingTouch.Region(document.getElementById('frame'));
    let childElement = document.getElementById('sliderTop');
    activeRegion.bind(childElement, 'pan', function (event) {
        console.log(event.detail.data[0]['distanceFromOrigin'] + 'PX');

        var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
        console.log(rad + 'Rad');

        var y = event.detail.data[0]['distanceFromOrigin'] * Math.sin(rad)
        console.log(y)
        if (y < -20) {
            document.getElementById('sliderTop').style.top = 0;
        } else if (y > 20) {
            var top = -1 * document.getElementById('col-waitinglist').clientHeight + 'px';
            var ele = document.getElementById('sliderTop');
            ele.style.top = top;
        }


    });
}

export {initDragDragger}