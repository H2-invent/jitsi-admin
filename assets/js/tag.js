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

export function updateTag(data) {
    const tagElement = document.getElementById('tagContent'); //FIXME duplicate ids?

    tagElement.outerHTML = data.html;
    tagElement.closest('.jitsiadminiframe').style.borderColor(data.color); //FIXME not working yet
}

