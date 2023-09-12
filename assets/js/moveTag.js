import $ from "jquery";

export function moveTag(frameDIv) {
    var tagContent = document.getElementById('tagContent');
    tagContent.classList.forEach(function (e) {
        tagContent.classList.remove(e);
    })
    tagContent.classList.add('floating-tag');
    if (showTagTransparent) {
        tagContent.classList.add('transparent-bg');

    }
    frameDIv.prepend(tagContent);
}