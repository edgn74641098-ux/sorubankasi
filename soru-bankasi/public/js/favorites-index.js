(function () {
    var selectAllButton = document.querySelector('.js-favorites-select-all');
    var clearAllButton = document.querySelector('.js-favorites-clear-all');

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

    document.addEventListener('submit', function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains('js-favorite-remove-form')) {
            return;
        }

        event.preventDefault();

        var tokenInput = form.querySelector('input[name="_token"]');
        if (!tokenInput) {
            form.submit();
            return;
        }

        var formData = new FormData(form);
        var button = form.querySelector('button[type="submit"]');
        if (button) {
            button.disabled = true;
        }

        fetch(form.action, {
            method: 'POST',
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
                'X-CSRF-TOKEN': tokenInput.value
            },
            body: formData,
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error('favorite-remove-failed');
                }

                var card = form.closest('.sb-stat-card');
                if (card) {
                    card.remove();
                }
            })
            .catch(function () {
                form.submit();
            })
            .finally(function () {
                if (button) {
                    button.disabled = false;
                }
            });
    });
})();
