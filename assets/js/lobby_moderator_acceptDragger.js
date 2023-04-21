import ZingTouch from 'zingtouch';
import $ from "jquery";
var blockTouch = false;
import {inIframe} from './moderatorIframe'
function initDragParticipants() {
    if(window.innerWidth > 768 ){
        return false;
    }
    var activeRegion = new ZingTouch.Region(document.getElementById('waitingUserWrapper'),null,false);
    let childElement = document.querySelectorAll('.waitingUserCard ');
    let x = 0;

    childElement.forEach(function (e) {
        var iconHolder = e.querySelector('.icon-holder');
        var height = $(e.querySelector('.card')).innerHeight();
        let width = $(e.querySelector('.card')).innerWidth();
        $(iconHolder).height(height+'px');
        $(iconHolder).width(width+'px');
        window.addEventListener('resize', function () {
            let childElement = document.querySelectorAll('.waitingUserCard ');
            childElement.forEach(function (e) {
                var iconHolder = e.querySelector('.icon-holder');
                var height = $(e.querySelector('.card')).innerHeight();
                let width = $(e.querySelector('.card')).innerWidth();
                $(iconHolder).height(height + 'px');
                $(iconHolder).width(width + 'px');
            });
        });
        activeRegion.bind(e, new ZingTouch.Pan({
            threshold: 0
        }), function (event) {
            blockTouch= true;
            var rad = event.detail.data[0]['directionFromOrigin'] / 360 * 2 * Math.PI;
            x = event.detail.data[0]['distanceFromOrigin'] * Math.cos(rad)
            event.target.querySelector('.card').style.transform = "translate("+x+"px,0)";
            if (x > 0){
                event.target.querySelector('.acceptSwipe').style.opacity = Math.abs(x/(width/2))
                event.target.querySelector('.denieSwipe').style.opacity = 0
            }else {
                event.target.querySelector('.denieSwipe').style.opacity = Math.abs(x/(width/2))
                event.target.querySelector('.acceptSwipe').style.opacity = 0
            }
        });

        e.addEventListener('touchend', function (e) {
            blockTouch = false;
             if (Math.abs(x) > $(this).width()/2){
                if (x > 0){
                    $.get(this.querySelector('.acceptSwipe').dataset.target)
                }else {
                    $.get(this.querySelector('.denieSwipe').dataset.target)
                }
             }else {
                 var ele = this.querySelector('.card')
                 ele.style.transition = "transform 0.2s";
                 ele.style.transform = "translate(0px,0)";
                 setTimeout(function () {
                     ele.style.transition = "transform 0s";
                 },200);

             }
             x = 0;
        });
    })


}

export {initDragParticipants, blockTouch}
