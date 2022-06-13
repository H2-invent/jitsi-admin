import ZingTouch from 'zingtouch';

function initDragParticipants() {


    var activeRegion = new ZingTouch.Region(document.getElementById('waitingUserWrapper'),null,false);
    let childElement = document.querySelectorAll('.waitingUserCard ');
    let x = 0;
    childElement.forEach(function (e) {
        var iconHolder = e.querySelector('.icon-holder');
        var height = $(e.querySelector('.card')).innerHeight();
        let width = $(e.querySelector('.card')).innerWidth();
        $(iconHolder).height(height+'px');
        $(iconHolder).width(width+'px');
        activeRegion.bind(e, new ZingTouch.Pan({
            threshold: 0
        }), function (event) {
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
             if (Math.abs(x) > $(this).width()/2){
                if (x > 0){
                    $.get(this.querySelector('.acceptSwipe').dataset.target)
                    console.log('accept');

                }else {
                    $.get(this.querySelector('.denieSwipe').dataset.target)
                    console.log('denie');
                }
             }else {
                 var ele = this.querySelector('.card').style.transform = "translate(0px,0)";
                 event.target.querySelector('.feedbackSwipe').style.opacity = 0;
             }

             x = 0;
        });
    })


}

export {initDragParticipants}