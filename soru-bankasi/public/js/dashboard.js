document.addEventListener('click', async function (event) {
    var link = event.target.closest('#son-testler .pagination a');
    if (!link) {
        return;
    }

    event.preventDefault();

    var section = document.getElementById('son-testler');
    if (!section) {
        window.location.href = link.href;
        return;
    }

    section.setAttribute('aria-busy', 'true');

    try {
        var response = await fetch(link.href, {
            headers: {
                'X-Requested-With': 'XMLHttpRequest',
            },
        });

        if (!response.ok) {
            window.location.href = link.href;
            return;
        }

        var html = await response.text();
        var doc = new DOMParser().parseFromString(html, 'text/html');
        var nextSection = doc.getElementById('son-testler');

        if (!nextSection) {
            window.location.href = link.href;
            return;
        }

        section.replaceWith(nextSection);
        window.history.pushState({}, '', link.href);
    } catch (error) {
        window.location.href = link.href;
    }
});
