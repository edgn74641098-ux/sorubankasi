(function () {
    var pendingController = null;
    var isBinding = false;

    function parseCardFromHtml(html) {
        var parser = new DOMParser();
        var nextDoc = parser.parseFromString(html, "text/html");
        return nextDoc.getElementById("subjectLeaderboardCard");
    }

    function syncUrl(filterForm) {
        var formData = new FormData(filterForm);
        var params = new URLSearchParams();
        formData.forEach(function (value, key) {
            if (value !== null && value !== "") {
                params.set(key, String(value));
            }
        });

        var actionUrl = new URL(filterForm.action, window.location.origin);
        actionUrl.search = params.toString();
        return actionUrl;
    }

    function bind() {
        if (isBinding) {
            return;
        }

        var filterForm = document.getElementById("subjectLeaderboardFilterForm");
        var subjectSelect = document.querySelector("[data-ajax-subject-filter]");
        var card = document.getElementById("subjectLeaderboardCard");

        if (!filterForm || !subjectSelect || !card) {
            return;
        }

        isBinding = true;
        subjectSelect.addEventListener("change", function () {
            isBinding = false;
        if (pendingController) {
            pendingController.abort();
        }

        var requestUrl = syncUrl(filterForm);
        pendingController = new AbortController();
        subjectSelect.disabled = true;

        fetch(requestUrl.toString(), {
            method: "GET",
            headers: {
                "X-Requested-With": "XMLHttpRequest"
            },
            credentials: "same-origin",
            signal: pendingController.signal
        })
            .then(function (response) {
                if (!response.ok) {
                    throw new Error("leaderboard-fetch-failed");
                }
                return response.text();
            })
            .then(function (html) {
                var nextCard = parseCardFromHtml(html);
                if (!nextCard) {
                    throw new Error("leaderboard-card-not-found");
                }

                card.replaceWith(nextCard);
                history.replaceState({}, "", requestUrl.toString());
                bind();
            })
            .catch(function (error) {
                if (error && error.name === "AbortError") {
                    return;
                }
                filterForm.submit();
            })
            .finally(function () {
                subjectSelect.disabled = false;
            });
        }, { once: true });
    }

    bind();
})();
