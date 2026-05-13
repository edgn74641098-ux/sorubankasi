(function () {
    var questionText = document.getElementById('question_text');
    var explanationText = document.getElementById('explanation_text');
    var charCount = document.getElementById('char-count');
    var explanationCount = document.getElementById('explanation-count');

    if (!questionText || !explanationText || !charCount || !explanationCount) {
        return;
    }

    var syncCounts = function () {
        charCount.textContent = String(questionText.value.length);
        explanationCount.textContent = String(explanationText.value.length);
    };

    questionText.addEventListener('input', syncCounts);
    explanationText.addEventListener('input', syncCounts);
    syncCounts();
})();
