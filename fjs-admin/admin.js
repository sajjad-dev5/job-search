// fjs-admin/admin.js

document.addEventListener('DOMContentLoaded', () => {

    // ── Active sidebar link ───────────────────────────────
    const page = window.location.pathname.split('/').pop();
    document.querySelectorAll('.sidebar nav a').forEach(link => {
        if (link.getAttribute('href') === page) {
            link.classList.add('active');
        }
    });

    // ── Confirm delete dialogs ────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            if (!confirm(el.dataset.confirm || 'Are you sure?')) {
                e.preventDefault();
            }
        });
    });

    // ── Table live search ─────────────────────────────────
    const searchInput = document.getElementById('tableSearch');
    if (searchInput) {
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase();
            document.querySelectorAll('tbody tr').forEach(row => {
                row.style.display = row.textContent.toLowerCase().includes(query) ? '' : 'none';
            });
        });
    }

    // ── Auto-hide alerts ──────────────────────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 4000);
    });

    // Reveal password while hovering over the eye button
    document.querySelectorAll('[data-password-peek]').forEach(button => {
        const selector = button.getAttribute('data-password-peek');
        const field = selector ? document.querySelector(selector) : null;
        if (!field) return;

        const show = () => field.type = 'text';
        const hide = () => field.type = 'password';

        button.addEventListener('mouseenter', show);
        button.addEventListener('mouseleave', hide);
        button.addEventListener('focus', show);
        button.addEventListener('blur', hide);
        button.addEventListener('mousedown', e => e.preventDefault());
    });

});
