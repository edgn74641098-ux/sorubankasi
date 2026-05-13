(function () {
    var minSlider = document.getElementById('min_difficulty');
    var maxSlider = document.getElementById('max_difficulty');
    var minOutput = document.getElementById('minDifficultyValue');
    var maxOutput = document.getElementById('maxDifficultyValue');
    var difficultyBlock = document.querySelector('.js-difficulty-range-block');
    var modeInputs = document.querySelectorAll('input[name="mode"]');
    var subjectSelect = document.getElementById('subject_id');
    var weakQuestionModeCount = document.getElementById('weakQuestionModeCount');
    var solvedUniqueCount = document.getElementById('solvedUniqueCount');
    var remainingUniqueCount = document.getElementById('remainingUniqueCount');

    var syncDifficultyVisibility = function () {
        var selected = document.querySelector('input[name="mode"]:checked');
        if (!difficultyBlock || !selected) {
            return;
        }
        difficultyBlock.classList.toggle('d-none', selected.value !== 'DIFFICULTY_RANGE');
    };

    modeInputs.forEach(function (input) {
        input.addEventListener('change', syncDifficultyVisibility);
    });
    syncDifficultyVisibility();

    var syncWeakQuestionCount = function () {
        if (!subjectSelect || !weakQuestionModeCount) {
            return;
        }
        var selectedOption = subjectSelect.options[subjectSelect.selectedIndex];
        weakQuestionModeCount.textContent = (selectedOption && selectedOption.dataset.weakCount) || '0';
        if (solvedUniqueCount) {
            solvedUniqueCount.textContent = (selectedOption && selectedOption.dataset.solvedUniqueCount) || '0';
        }
        if (remainingUniqueCount) {
            remainingUniqueCount.textContent = (selectedOption && selectedOption.dataset.remainingUniqueCount) || '0';
        }
    };

    if (subjectSelect) {
        subjectSelect.addEventListener('change', syncWeakQuestionCount);
        syncWeakQuestionCount();
    }

    if (minSlider && maxSlider && minOutput && maxOutput) {
        var syncValues = function () {
            if (parseInt(minSlider.value, 10) > parseInt(maxSlider.value, 10)) {
                maxSlider.value = minSlider.value;
            }
            minOutput.textContent = minSlider.value;
            maxOutput.textContent = maxSlider.value;
        };

        minSlider.addEventListener('input', syncValues);
        maxSlider.addEventListener('input', syncValues);
        syncValues();
    }
})();
