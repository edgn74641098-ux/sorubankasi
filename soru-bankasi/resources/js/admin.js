import 'bootstrap';

const shell = document.getElementById('adminShell');
const toggle = document.getElementById('adminMenuToggle');
const sidebar = document.getElementById('adminSidebar');
const backdrop = document.getElementById('adminSidebarBackdrop');

const closeSidebar = () => {
    if (!shell || !toggle || !backdrop) {
        return;
    }

    shell.classList.remove('admin-shell--sidebar-open');
    toggle.setAttribute('aria-expanded', 'false');
    backdrop.hidden = true;
};

const openSidebar = () => {
    if (!shell || !toggle || !backdrop) {
        return;
    }

    shell.classList.add('admin-shell--sidebar-open');
    toggle.setAttribute('aria-expanded', 'true');
    backdrop.hidden = false;
};

if (toggle && sidebar && shell && backdrop) {
    toggle.addEventListener('click', () => {
        if (shell.classList.contains('admin-shell--sidebar-open')) {
            closeSidebar();
            return;
        }
        openSidebar();
    });

    backdrop.addEventListener('click', closeSidebar);

    sidebar.querySelectorAll('a').forEach((link) => {
        link.addEventListener('click', () => {
            if (window.innerWidth <= 900) {
                closeSidebar();
            }
        });
    });

    window.addEventListener('resize', () => {
        if (window.innerWidth > 900) {
            closeSidebar();
        }
    });
}
