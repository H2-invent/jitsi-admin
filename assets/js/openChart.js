function initOpenChart() {
    var ele = document.querySelectorAll('[data-mdb-toggle="openChart"]');
    ele.forEach(function (value, key, parent) {

        value.addEventListener('click', function () {
            var target = this.dataset.target;
            document.querySelector(target).classList.toggle('d-none')
        })
    })
}

export {initOpenChart}