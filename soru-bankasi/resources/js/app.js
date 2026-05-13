import './bootstrap';

import Alpine from 'alpinejs';

window.Alpine = Alpine;

Alpine.start();

var SCROLL_RESTORE_KEY = 'sb:scroll-restore';

function rememberScrollForSamePage(targetPathname) {
    if (!targetPathname || targetPathname !== window.location.pathname) {
        return;
    }

    sessionStorage.setItem(SCROLL_RESTORE_KEY, JSON.stringify({
        pathname: targetPathname,
        y: window.scrollY || window.pageYOffset || 0,
        ts: Date.now(),
    }));
}

function targetPathnameFromForm(form) {
    try {
        var action = form.getAttribute('action') || window.location.href;
        return new URL(action, window.location.href).pathname;
    } catch (error) {
        return null;
    }
}

function shouldSkipScrollRemember(form) {
    return !!(form && form.hasAttribute('data-scroll-restore-off'));
}

window.addEventListener('pageshow', function () {
    if (window.location.hash) {
        return;
    }

    var raw = sessionStorage.getItem(SCROLL_RESTORE_KEY);
    if (!raw) {
        return;
    }

    try {
        var payload = JSON.parse(raw);
        var isFresh = payload && typeof payload.ts === 'number' && (Date.now() - payload.ts) < 20000;
        var samePath = payload && payload.pathname === window.location.pathname;

        if (isFresh && samePath) {
            window.scrollTo(0, Number(payload.y || 0));
        }
    } catch (error) {
        // noop
    } finally {
        sessionStorage.removeItem(SCROLL_RESTORE_KEY);
    }
});

document.addEventListener('click', function (event) {
    var confirmTrigger = event.target.closest('[data-confirm]');
    if (confirmTrigger) {
        var confirmMessage = confirmTrigger.getAttribute('data-confirm');
        if (confirmMessage && !window.confirm(confirmMessage)) {
            event.preventDefault();
            event.stopPropagation();
            return;
        }
    }

    var submitTrigger = event.target.closest('[data-submit-form]');
    if (submitTrigger) {
        var formId = submitTrigger.getAttribute('data-submit-form');
        var form = document.getElementById(formId);
        if (form) {
            if (!shouldSkipScrollRemember(form)) {
                rememberScrollForSamePage(targetPathnameFromForm(form));
            }
            form.submit();
        }
    }
});

document.addEventListener('submit', function (event) {
    var form = event.target.closest('form');
    if (!form) {
        return;
    }

    var message = form.getAttribute('data-confirm');
    if (message && !window.confirm(message)) {
        event.preventDefault();
        return;
    }

    if (!shouldSkipScrollRemember(form)) {
        rememberScrollForSamePage(targetPathnameFromForm(form));
    }
});

document.addEventListener('change', function (event) {
    var el = event.target.closest('[data-autosubmit]');
    if (!el || !el.form) {
        return;
    }
    if (!shouldSkipScrollRemember(el.form)) {
        rememberScrollForSamePage(targetPathnameFromForm(el.form));
    }
    el.form.submit();
});

function syncReportDetails(root) {
    if (!root) {
        return;
    }
    var categoryField = root.querySelector('select[name="category"]');
    var correctWrap = root.querySelector('.js-correct-option-wrap');
    var correctField = root.querySelector('.js-correct-option-field');
    var subjectWrap = root.querySelector('.js-subject-wrap');
    var subjectField = root.querySelector('.js-subject-field');
    var typoWrap = root.querySelector('.js-typo-wrap');
    var typoFields = root.querySelectorAll('.js-typo-field');
    if (!categoryField) {
        return;
    }
    var wrongSubject = categoryField.value === 'WRONG_SUBJECT';
    var typoCategory = categoryField.value === 'TYPO';
    if (correctWrap) {
        correctWrap.classList.toggle('d-none', wrongSubject);
    }
    if (correctField) {
        correctField.required = !wrongSubject;
    }
    if (subjectWrap) {
        subjectWrap.classList.toggle('d-none', !wrongSubject);
    }
    if (subjectField) {
        subjectField.required = wrongSubject;
    }
    if (typoWrap) {
        typoWrap.classList.toggle('d-none', !typoCategory);
    }
    typoFields.forEach(function (field) {
        field.required = typoCategory;
    });
}

document.querySelectorAll('.js-report-details').forEach(function (root) {
    syncReportDetails(root);
});

document.addEventListener('change', function (event) {
    var category = event.target.closest('.js-report-details select[name="category"]');
    if (!category) {
        return;
    }
    syncReportDetails(category.closest('.js-report-details'));
});
