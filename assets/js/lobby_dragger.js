import ZingTouch from 'zingtouch';

function initDragDragger() {
    // var activeRegion = new ZingTouch.Region(document.getElementById('lobbyWindow'),null,false);
    // let childElement = document.getElementById('sliderTop');
    // activeRegion.bind(childElement, 'pan', function (event) {
    //     var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
    //     var y = event.detail.data[0]['distanceFromOrigin'] * Math.sin(rad)
    //
    //     if (y < -2) {
    //         document.getElementById('sliderTop').style.top = 0;
    //     } else if (y > 20) {
    //         var top = -1 * document.getElementById('col-waitinglist').clientHeight + 'px';
    //         var ele = document.getElementById('sliderTop');
    //         ele.style.top = top;
    //         document.getElementById('dragger').classList.remove('active');
    //
    //     }
    // },true);
    let startX = 0;
    let startY = 0;
    document.getElementById('frame').addEventListener('touchstart', function (event) {
        //console.log(event);
        startX = event.touches[0].clientX;
        startY = event.touches[0].clientY;
        console.log(`the start is at X: ${startX}px and the Y is at ${startY}px`)

    })

    document.getElementById('frame').addEventListener('touchend', function (event) {
        //console.log(event);
        let endX = event.changedTouches[0].clientX;
        let endY = event.changedTouches[0].clientY;
        console.log(`the start is at X: ${endX}px and the Y is at ${endY}px`)

        var xDist = endX - startX;
        var yDist = endY - startY;
        if (Math.abs(yDist) > 50) {
            if (yDist < 0) {
                var top = -1 * document.getElementById('col-waitinglist').clientHeight + 'px';
                var ele = document.getElementById('sliderTop');
                ele.style.top = top;
                document.getElementById('dragger').classList.remove('active');

            } else {
                document.getElementById('sliderTop').style.top = 0;
            }
        }
    })


}

export {initDragDragger}