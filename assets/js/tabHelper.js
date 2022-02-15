import $ from "jquery";


function initTabs() {
    initalSetUnderline();
    $(window).resize(function () {
        initalSetUnderline();
    })

    $('.nav-item').click(function () {
        var ele = $(this);
        var parent = ele.closest('.nav-tabs');
        var eleOld = parent.find('.active').closest('.nav-item');
        var parentwidth = parent.width();
        var eleWidth = ele.width();


        var oldRight = eleOld.position().left + eleOld.width();
        var oldLeft = eleOld.position().left;

        var newRight = ele.position().left + ele.width();
        var newLeft = ele.position().left;
        var oldWidth = ele.width();
        var tmpWidth = oldRight - newLeft;
        if (oldLeft < newLeft) {
            tmpWidth = newRight - oldLeft;
        }

        if (newLeft < oldLeft) {//we move to the left
            parent.find('.underline').css('transform', 'translateX(' + newLeft / parentwidth * 100 + '%) scaleX(' + tmpWidth / parentwidth + ')');
        } else {
            parent.find('.underline').css('transform', 'translateX(' + oldLeft / parentwidth * 100 + '%) scaleX(' + tmpWidth / parentwidth + ')');
        }

        var leftAtfter = newLeft / parentwidth * 100;
        var widthAfter = eleWidth / parentwidth;
        setTimeout(function () {
            parent.find('.underline').css('transform', 'translateX(' + leftAtfter + '%) scaleX(' + widthAfter + ')');
        }, 180);
    })
}

function initalSetUnderline() {
    $('.underline').each(function (e) {
        var ele = $(this).closest('.nav-tabs').find('.active').closest('.nav-item');
        var newLeft = ele.position().left;
        var parent = ele.closest('.nav-tabs');
        var parentwidth = parent.width();

        var leftAtfter = newLeft / parentwidth * 100;
        var widthAfter = ele.width() / parentwidth;
        parent.find('.underline').css('transform', 'translateX(' + leftAtfter + '%) scaleX(' + widthAfter + ')').css('display','block');

    })
}
export {initTabs}

