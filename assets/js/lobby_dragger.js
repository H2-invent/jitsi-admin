import ZingTouch from 'zingtouch';

function initDragDragger() {
    var activeRegion = new ZingTouch.Region(document.getElementById('frame'),'pane',false);
    let childElement = document.getElementById('sliderTop');
    activeRegion.bind(childElement, 'pan', function (event) {
        var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
        var y = event.detail.data[0]['distanceFromOrigin'] * Math.sin(rad)
        if (y < -20) {
            document.getElementById('sliderTop').style.top = 0;
        } else if (y > 20) {
            var top = -1 * document.getElementById('col-waitinglist').clientHeight + 'px';
            var ele = document.getElementById('sliderTop');
            ele.style.top = top;
            document.getElementById('dragger').classList.remove('active');

        }
    });
}

export {initDragDragger}