(function () {
    var selectAllButton = document.querySelector('.js-favorites-select-all');
    var clearAllButton = document.querySelector('.js-favorites-clear-all');

    if (!selectAllButton && !clearAllButton) {
        return;
    }

    var setChecked = function (checked) {
        document.querySelectorAll('.js-favorite-pdf-checkbox').forEach(function (checkbox) {
            checkbox.checked = checked;
        });
    };

    if (selectAllButton) {
        selectAllButton.addEventListener('click', function () {
            setChecked(true);
        });
    }

    if (clearAllButton) {
        clearAllButton.addEventListener('click', function () {
            setChecked(false);
        });
    }
})();
