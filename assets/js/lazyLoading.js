import $ from "jquery";
import {initAllComponents} from "./confirmation";

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
                        window.dashboardLazyLoaded = true;
                        var parent = entry.target.parentNode;
                        parent. insertAdjacentHTML('beforeend', data);
                        // The lazy-loaded fragment contains MDB components (dropdowns,
                        // popovers, tooltips, ...). They only get initialized when the
                        // component init helpers run, so initialize them now — otherwise
                        // e.g. the "Options" dropdown buttons in the newly appended room
                        // cards have no instance attached and do not respond to clicks.
                        initAllComponents();
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
