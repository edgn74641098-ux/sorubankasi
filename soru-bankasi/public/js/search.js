(function () {
    var toggle = document.getElementById("use_difficulty");
    var row = document.getElementById("difficulty-range-row");
    var minSlider = document.getElementById("min_difficulty");
    var maxSlider = document.getElementById("max_difficulty");
    var minOutput = document.getElementById("minDifficultyValue");
    var maxOutput = document.getElementById("maxDifficultyValue");

    if (toggle && row) {
        function syncDifficultyVisibility() {
            row.classList.toggle("d-none", !toggle.checked);
        }

        toggle.addEventListener("change", syncDifficultyVisibility);
        syncDifficultyVisibility();
    }

    if (minSlider && maxSlider) {
        var syncRangeValues = function () {
            if (parseFloat(minSlider.value) > parseFloat(maxSlider.value)) {
                maxSlider.value = minSlider.value;
            }

            if (minOutput) {
                minOutput.textContent = Number(minSlider.value).toFixed(1);
            }
            if (maxOutput) {
                maxOutput.textContent = Number(maxSlider.value).toFixed(1);
            }
        };

        minSlider.addEventListener("input", syncRangeValues);
        maxSlider.addEventListener("input", syncRangeValues);
        syncRangeValues();
    }

    function setFavoriteUi(form, favorited) {
        var icon = form.querySelector(".js-favorite-icon");
        var button = form.querySelector("button[type='submit']");
        if (!icon || !button) {
            return;
        }

        form.dataset.favorited = favorited ? "1" : "0";
        icon.classList.toggle("bi-star-fill", favorited);
        icon.classList.toggle("bi-star", !favorited);
        button.setAttribute("title", favorited ? "Favorilerden cikar" : "Favorilere ekle");
        button.setAttribute("aria-label", favorited ? "Favorilerden cikar" : "Favorilere ekle");
    }

    function syncFavoriteMethod(form, favorited) {
        var methodInput = form.querySelector("input[name='_method']");
        if (favorited) {
            if (!methodInput) {
                methodInput = document.createElement("input");
                methodInput.type = "hidden";
                methodInput.name = "_method";
                form.appendChild(methodInput);
            }
            methodInput.value = "DELETE";
            form.action = form.dataset.destroyUrl;
        } else {
            if (methodInput) {
                methodInput.remove();
            }
            form.action = form.dataset.storeUrl;
        }
    }

    document.addEventListener("submit", function (event) {
        var form = event.target;
        if (!(form instanceof HTMLFormElement) || !form.classList.contains("js-favorite-toggle-form")) {
            return;
        }

        event.preventDefault();
        var currentlyFavorited = form.dataset.favorited === "1";
        var tokenInput = form.querySelector("input[name='_token']");
        if (!tokenInput) {
            form.submit();
            return;
        }

        var formData = new FormData(form);
        var button = form.querySelector("button[type='submit']");
        if (button) {
            button.disabled = true;
        }

        fetch(form.action, {
            method: "POST",
            headers: {
                "X-Requested-With": "XMLHttpRequest",
                "X-CSRF-TOKEN": tokenInput.value
            },
            body: formData,
            credentials: "same-origin"
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("favorite-request-failed");
                }

                var nextFavorited = !currentlyFavorited;
                setFavoriteUi(form, nextFavorited);
                syncFavoriteMethod(form, nextFavorited);
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
