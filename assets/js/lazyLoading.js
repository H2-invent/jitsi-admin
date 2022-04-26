import $ from "jquery";

function initLayzLoading(){
    const lazyBg = document.querySelectorAll(".lazyLoad");
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
                       lazyBackgroundObserver.observe(newLazy);
                   });

           }
        },{});
    });

    lazyBg.forEach ( function (lazyBackground) {
        lazyBackgroundObserver.observe(lazyBackground);
    });

}
export {initLayzLoading}
