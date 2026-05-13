(function () {
    var page = document.getElementById("testsShowPage");
    if (!page) {
        return;
    }

    var restoreScroll = Number(page.getAttribute("data-restore-scroll") || "0");
    if (Number.isFinite(restoreScroll) && restoreScroll > 0) {
        document.documentElement.classList.add("sb-test-restore-lock");
        history.scrollRestoration = "manual";
        window.scrollTo(0, restoreScroll);
        var unlock = function () {
            window.scrollTo(0, restoreScroll);
            document.documentElement.classList.remove("sb-test-restore-lock");
        };
        window.addEventListener("DOMContentLoaded", unlock, { once: true });
        window.addEventListener("load", unlock, { once: true });
    }

    var container = document.getElementById("questionContainer");
    var fontSizeSelect = document.getElementById("fontSizeSelect");
    var timer = document.getElementById("remainingTimer");
    var noteTextarea = document.getElementById("reportNote");
    var charCount = document.getElementById("charCount");
    var scrollYInput = document.getElementById("scrollYInput");
    var answerForm = document.getElementById("testAnswerForm");
    var reportCategory = document.getElementById("reportCategory");
    var reportCorrectOptionWrap = document.getElementById("reportCorrectOptionWrap");
    var reportCorrectOption = document.getElementById("suggestedCorrectOption");
    var reportSubjectWrap = document.getElementById("reportSubjectWrap");
    var reportSubject = document.getElementById("suggestedSubject");
    var reportTypoWrap = document.getElementById("reportTypoWrap");
    var reportTypoFields = document.querySelectorAll(".report-typo-field");

    var storageKey = "test-font-size";
    var applyFontSize = function (value) {
        if (!container) {
            return;
        }

        container.classList.remove("fs-6", "fs-5", "fs-4");
        if (value === "large") {
            container.classList.add("fs-5");
        } else if (value === "xlarge") {
            container.classList.add("fs-4");
        } else {
            container.classList.add("fs-6");
        }
    };

    if (fontSizeSelect) {
        var saved = localStorage.getItem(storageKey) || "base";
        fontSizeSelect.value = saved;
        applyFontSize(saved);
        fontSizeSelect.addEventListener("change", function () {
            localStorage.setItem(storageKey, fontSizeSelect.value);
            applyFontSize(fontSizeSelect.value);
        });
    }

    var remainingSeconds = Number(page.getAttribute("data-remaining-seconds") || "0");
    var renderTimer = function () {
        if (!timer) {
            return;
        }
        var minutes = String(Math.floor(remainingSeconds / 60)).padStart(2, "0");
        var seconds = String(remainingSeconds % 60).padStart(2, "0");
        timer.textContent = minutes + ":" + seconds;
    };

    if (timer && Number.isFinite(remainingSeconds)) {
        renderTimer();
        var interval = setInterval(function () {
            if (remainingSeconds <= 0) {
                clearInterval(interval);
                window.location.reload();
                return;
            }
            remainingSeconds -= 1;
            renderTimer();
        }, 1000);
    }

    if (noteTextarea && charCount) {
        noteTextarea.addEventListener("input", function () {
            charCount.textContent = String(noteTextarea.value.length);
        });
    }

    var syncReportFields = function () {
        if (!reportCategory) {
            return;
        }

        var wrongSubject = reportCategory.value === "WRONG_SUBJECT";
        var typoCategory = reportCategory.value === "TYPO";

        if (reportCorrectOptionWrap) {
            reportCorrectOptionWrap.classList.toggle("d-none", wrongSubject);
        }
        if (reportCorrectOption) {
            reportCorrectOption.required = !wrongSubject;
        }

        if (reportSubjectWrap) {
            reportSubjectWrap.classList.toggle("d-none", !wrongSubject);
        }
        if (reportSubject) {
            reportSubject.required = wrongSubject;
        }

        if (reportTypoWrap) {
            reportTypoWrap.classList.toggle("d-none", !typoCategory);
        }
        reportTypoFields.forEach(function (field) {
            field.required = typoCategory;
        });
    };

    if (reportCategory) {
        reportCategory.addEventListener("change", syncReportFields);
        syncReportFields();
    }

    if (answerForm && scrollYInput) {
        answerForm.addEventListener("submit", function () {
            scrollYInput.value = String(Math.max(0, Math.round(window.scrollY)));
        });
    }

    function setFavoriteUi(form, favorited) {
        var icon = form.querySelector(".js-test-favorite-icon");
        var button = form.querySelector("button[type='submit']");
        if (!icon || !button) {
            return;
        }

        form.dataset.favorited = favorited ? "1" : "0";
        icon.classList.toggle("bi-star-fill", favorited);
        icon.classList.toggle("bi-star", !favorited);
        button.setAttribute("title", favorited ? "Favoriden cikar" : "Favoriye ekle");
        button.setAttribute("aria-label", favorited ? "Favoriden cikar" : "Favoriye ekle");
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
        if (!(form instanceof HTMLFormElement) || !form.classList.contains("js-test-favorite-form")) {
            return;
        }

        event.preventDefault();

        var tokenInput = form.querySelector("input[name='_token']");
        if (!tokenInput) {
            form.submit();
            return;
        }

        var currentlyFavorited = form.dataset.favorited === "1";
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
                    throw new Error("test-favorite-toggle-failed");
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
