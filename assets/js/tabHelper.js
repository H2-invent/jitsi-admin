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
        var direction = newLeft < oldLeft ? 1 : 0;

        if (direction === 1) {//we move to the left
            parent.find('.underline').css('transform', 'translateX(' + newLeft / parentwidth * 100 + '%) scaleX(' + tmpWidth / parentwidth + ')');
        } else {
            parent.find('.underline').css('transform', 'translateX(' + oldLeft / parentwidth * 100 + '%) scaleX(' + tmpWidth / parentwidth + ')');
        }

        var leftAtfter = newLeft / parentwidth * 100;
        var widthAfter = eleWidth / parentwidth;
        setTimeout(function () {
            parent.find('.underline').css('transform', 'translateX(' + leftAtfter + '%) scaleX(' + widthAfter + ')');
        }, 180);

        changeTabContent(ele.find('a').attr('href'), direction);
    })

    $('.dropdownTabToggle').click(function (e) {
        e.preventDefault();
        var $ele = $(this);
        var $target = $ele.attr('href');
        changeTabContent($($target).attr('href'), 1);
        $ele.closest('.dropdown-menu').find('.dropdown-item').each(function () {
            $(this).removeClass('active');
        })
        $ele.addClass('active');
        $ele.closest('.tabDropdown').find('button').text($ele.text());
    })
}


function changeTabContent(href, direction = 1) {
    var target = $(href);
    var oldEle = target.closest('.tab-content').find('.active')
    if(target.hasClass('active')){
        return false;
    }
    target.addClass('noAnimation')
    if (direction === 1) {//we move to the left
        target.css('transform', 'translateX(-110%)')
        oldEle.find('.tab-pane').css('transform', 'translateX(110%)');
    } else {
        oldEle.find('.tab-pane').css('transform', 'translateX(-110%)');
        target.css('transform', 'translateX(110%)')
    }
    setTimeout(function () {
        target.removeClass('noAnimation')
        oldEle.removeClass('active');
        target.closest('.tab-content-watch').addClass('active');
        target.css('transform', 'translateX(0%)');
        target.closest('.tab-content-watch').addClass('active');
    },0);
        return  true;
}

function initalSetUnderline() {
    $('.underline').each(function (e) {
        var ele = $(this).closest('.nav-tabs').find('.active').closest('.nav-item');
        var newLeft = ele.position().left;
        var parent = ele.closest('.nav-tabs');
        var parentwidth = parent.width();

        var leftAtfter = newLeft / parentwidth * 100;
        var widthAfter = ele.width() / parentwidth;
        parent.find('.underline').css('transform', 'translateX(' + leftAtfter + '%) scaleX(' + widthAfter + ')').css('display', 'block');

    })
}

export {initTabs}

