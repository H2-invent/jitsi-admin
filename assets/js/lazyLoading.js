import $ from "jquery";

function initLayzLoading(){
    const lazyBg = document.querySelectorAll(".lazyLoad");
    lazyBg.forEach ( function (lazyBackground) {
        initLazyElemt(lazyBackground)

    });

}
function initLazyElemt(element){
    const lazyBackgroundObserver = new IntersectionObserver (function (entries, observer) {
        entries.forEach(function(entry) {
            if (entry.isIntersecting) {
                fetch(entry.target.dataset.target)
                    .then(response =>response.text())
                    .then(function (data) {
                        var parent = entry.target.parentNode;
                        parent. insertAdjacentHTML('beforeend', data);
                        entry.target.remove();
                        var newLazy = parent.querySelector('.lazyLoad')
                        if (newLazy){
                            lazyBackgroundObserver.observe(newLazy);
                        }

                    });

            }
        },{});
    });
    lazyBackgroundObserver.observe(element);
}

export {initLayzLoading,initLazyElemt}
