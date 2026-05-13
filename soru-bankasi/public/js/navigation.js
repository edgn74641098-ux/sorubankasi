(function () {
    var toggler = document.getElementById("appNavbarToggler");
    var panel = document.getElementById("appNavbar");
    var backdrop = document.getElementById("appNavbarBackdrop");

    if (!toggler || !panel) {
        return;
    }

    var closeMenu = function () {
        panel.classList.remove("is-open");
        toggler.setAttribute("aria-expanded", "false");
        document.body.classList.remove("sb-menu-open");
        if (backdrop) {
            backdrop.hidden = true;
        }
    };

    var openMenu = function () {
        panel.classList.add("is-open");
        toggler.setAttribute("aria-expanded", "true");
        document.body.classList.add("sb-menu-open");
        if (backdrop) {
            backdrop.hidden = false;
        }
    };

    toggler.addEventListener("click", function () {
        var open = panel.classList.contains("is-open");
        if (open) {
            closeMenu();
            return;
        }
        openMenu();
    });

    if (backdrop) {
        backdrop.addEventListener("click", closeMenu);
    }

    panel.querySelectorAll("a").forEach(function (link) {
        link.addEventListener("click", function () {
            if (window.innerWidth < 992) {
                closeMenu();
            }
        });
    });

    window.matchMedia("(min-width: 992px)").addEventListener("change", function (e) {
        if (e.matches) {
            closeMenu();
        }
    });
})();
