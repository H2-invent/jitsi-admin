import ZingTouch from 'zingtouch';
import $ from "jquery";
import {blockTouch} from './lobby_moderator_acceptDragger'
import {inIframe} from "./moderatorIframe";

function initDragDragger() {
    if(window.innerWidth > 768 ){
        document.getElementById('dragger').addEventListener('click',function (e) {

        })
        return false;
    }
    let  blockTouchInternal = false;
    var activeRegion = new ZingTouch.Region(document.getElementById('frame'), null, false);
    let childElement = document.getElementById('frame');
    let y = 0;
    let topnew;
    document.getElementById('waitingUserWrapper').addEventListener('touchstart',function (e) {
        var $content =document.getElementById('waitingUserWrapper');
        var isOverflowing = $content.clientHeight < $content.scrollHeight
        if(isOverflowing){
            blockTouchInternal = true
        }
    });
    document.getElementById('waitingUserWrapper').addEventListener('touchend',function (e) {
        var $content =document.getElementById('waitingUserWrapper');
        var isOverflowing = $content.clientWidth < $content.scrollWidth
        blockTouchInternal = false
    })
    activeRegion.bind(childElement, new ZingTouch.Pan({
        threshold: 2
    }), function (event) {
        if (blockTouch || blockTouchInternal) {
            return false;
        }
        var ele = document.getElementById('sliderTop');
        var ele2 = document.getElementById('col-waitinglist')
        var maxHeight = ele2.clientHeight;
        var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
        var ynew = event.detail.data[0]['distanceFromOrigin'] * Math.sin(rad)
        var delta = y - ynew;
        y = ynew;
        topnew = Number(ele.style.transform.replace(/px\)$/, '').replace(/translateY\(/, '')) + delta;
        if (topnew < 0 && topnew > -1 * maxHeight) {
            ele.style.transform = 'translateY(' + topnew + 'px)';
        }
    });
    childElement.addEventListener('touchend', function (e) {

        y = 0;
        if (blockTouch || blockTouchInternal) {
            return false;
        }
        var ele = document.getElementById('sliderTop');
        var ele2 = document.getElementById('col-waitinglist')
        var maxHeight = ele2.clientHeight;
        document.querySelector('.dragger').classList.remove('active');

        if (Math.abs(topnew) > Math.abs(maxHeight) / 2) {
            ele.style.transform = 'translateY(' + -1 * maxHeight + 'px)';
        } else {
            ele.style.transform = 'translateY(0px)';
        }
    });
}

export {initDragDragger}
