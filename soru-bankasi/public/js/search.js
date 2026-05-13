(function () {
    var toggle = document.getElementById("use_difficulty");
    var row = document.getElementById("difficulty-range-row");
    var minSlider = document.getElementById("min_difficulty");
    var maxSlider = document.getElementById("max_difficulty");
    var minOutput = document.getElementById("minDifficultyValue");
    var maxOutput = document.getElementById("maxDifficultyValue");

    if (!toggle || !row) {
        return;
    }

    function syncDifficultyVisibility() {
        row.classList.toggle("d-none", !toggle.checked);
    }

    toggle.addEventListener("change", syncDifficultyVisibility);
    syncDifficultyVisibility();

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
})();
