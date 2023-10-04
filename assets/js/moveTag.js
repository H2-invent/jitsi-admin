export function moveTag(frameDIv) {
    var tagContent = document.getElementById('tagContent');
    if (tagContent){
        tagContent.classList.forEach(function (e) {
            tagContent.classList.remove(e);
        })
        tagContent.classList.add('floating-tag');

        frameDIv.prepend(tagContent);
    }
}