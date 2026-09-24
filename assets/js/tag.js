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
    const frameTag = document.querySelector('#tagContent');
    if (frameTag) {
        frameTag.innerHTML = data.html ?? '';
    }

    // update the window border by posting message to outer frame
    const message = JSON.stringify({
        scope: 'jitsi-admin-iframe',
        type: 'updateTag',
        color: data.color,
    });

    window.parent.postMessage(message, '*');
}

