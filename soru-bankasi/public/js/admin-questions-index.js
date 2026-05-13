(function () {
    var selectAll = document.querySelector('.js-question-select-all');
    if (!selectAll) {
        return;
    }

    selectAll.addEventListener('change', function (event) {
        document.querySelectorAll('.js-question-select').forEach(function (checkbox) {
            checkbox.checked = event.target.checked;
            var hidden = document.querySelector('.js-question-activate-select[value="' + checkbox.value + '"]');
            if (hidden) {
                hidden.checked = checkbox.checked;
            }
        });
    });

    document.querySelectorAll('.js-question-select').forEach(function (checkbox) {
        checkbox.addEventListener('change', function () {
            var hidden = document.querySelector('.js-question-activate-select[value="' + checkbox.value + '"]');
            if (hidden) {
                hidden.checked = checkbox.checked;
            }
        });
    });
})();
