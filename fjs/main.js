// ============================================================
//  fjs/main.js — JobSearch Frontend JavaScript
// ============================================================

document.addEventListener('DOMContentLoaded', () => {

    // ── Mark active nav link ──────────────────────────────
    const currentPage = window.location.pathname.split('/').pop();
    document.querySelectorAll('nav .nav-links a').forEach(link => {
        if (link.getAttribute('href') === currentPage) {
            link.classList.add('active');
        }
    });

    // ── Auto-hide alerts after 5 seconds ─────────────────
    document.querySelectorAll('.alert').forEach(alert => {
        setTimeout(() => {
            alert.style.transition = 'opacity 0.5s';
            alert.style.opacity    = '0';
            setTimeout(() => alert.remove(), 500);
        }, 5000);
    });

    // ── Confirm delete dialogs ────────────────────────────
    document.querySelectorAll('[data-confirm]').forEach(el => {
        el.addEventListener('click', e => {
            const msg = el.dataset.confirm || 'Are you sure?';
            if (!confirm(msg)) e.preventDefault();
        });
    });

    // ── Character counter for textareas ──────────────────
    document.querySelectorAll('textarea[maxlength]').forEach(ta => {
        const max     = parseInt(ta.getAttribute('maxlength'));
        const counter = document.createElement('small');
        counter.style.cssText = 'color:#999; float:right; margin-top:4px;';
        ta.parentNode.appendChild(counter);
        const update = () => counter.textContent = `${ta.value.length} / ${max}`;
        ta.addEventListener('input', update);
        update();
    });

    // ── Job card apply button loading state ──────────────
    document.querySelectorAll('form .btn-primary[type="submit"]').forEach(btn => {
        btn.closest('form')?.addEventListener('submit', () => {
            btn.textContent = 'Please wait...';
            btn.disabled    = true;
        });
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
