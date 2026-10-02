/* =============================================================
   Employee Management System — app.js
   Client-side validation, confirm dialogs, mobile sidebar,
   dismissible alerts, and live table search.
   ============================================================= */
(function () {
    'use strict';

    document.addEventListener('DOMContentLoaded', function () {

        /* ---------- Mobile sidebar ---------- */
        var hamburger = document.getElementById('hamburger');
        var sidebar   = document.getElementById('sidebar');
        var overlay   = document.getElementById('sidebarOverlay');

        function closeSidebar() {
            if (sidebar)  sidebar.classList.remove('open');
            if (overlay)  overlay.classList.remove('open');
            if (hamburger) hamburger.setAttribute('aria-expanded', 'false');
        }

        if (hamburger && sidebar && overlay) {
            hamburger.addEventListener('click', function () {
                var isOpen = sidebar.classList.toggle('open');
                overlay.classList.toggle('open', isOpen);
                hamburger.setAttribute('aria-expanded', String(isOpen));
            });
            overlay.addEventListener('click', closeSidebar);
            document.addEventListener('keydown', function (ev) {
                if (ev.key === 'Escape') closeSidebar();
            });
        }

        /* ---------- Dismissible alerts ---------- */
        document.querySelectorAll('.alert-close').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var alert = btn.closest('.alert');
                if (alert) alert.remove();
            });
        });

        /* ---------- Confirm dialogs (delete / destructive actions) ---------- */
        document.querySelectorAll('[data-confirm]').forEach(function (el) {
            el.addEventListener('click', function (ev) {
                if (!window.confirm(el.getAttribute('data-confirm'))) {
                    ev.preventDefault();
                    ev.stopPropagation();
                }
            });
        });

        /* ---------- Live table search ---------- */
        document.querySelectorAll('[data-table-search]').forEach(function (input) {
            var target = document.querySelector(input.getAttribute('data-table-search'));
            if (!target) return;
            input.addEventListener('input', function () {
                var q = input.value.trim().toLowerCase();
                target.querySelectorAll('tbody tr').forEach(function (row) {
                    row.style.display = row.textContent.toLowerCase().indexOf(q) !== -1 ? '' : 'none';
                });
            });
        });

        /* ---------- Form validation helpers ---------- */
        function showError(field, message) {
            field.classList.add('input-invalid');
            var err = field.parentElement.querySelector('.field-error');
            if (!err) {
                err = document.createElement('div');
                err.className = 'field-error';
                field.parentElement.appendChild(err);
            }
            err.textContent = message;
            err.style.display = 'block';
        }

        function clearError(field) {
            field.classList.remove('input-invalid');
            var err = field.parentElement.querySelector('.field-error');
            if (err) err.style.display = 'none';
        }

        function validateForm(form) {
            var valid = true;
            var firstInvalid = null;

            form.querySelectorAll('input, select, textarea').forEach(function (field) {
                clearError(field);
                var value = (field.value || '').trim();

                if (field.hasAttribute('required') && value === '') {
                    showError(field, 'This field is required.');
                    valid = false;
                    firstInvalid = firstInvalid || field;
                    return;
                }
                if (value !== '' && field.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(value)) {
                    showError(field, 'Please enter a valid email address.');
                    valid = false;
                    firstInvalid = firstInvalid || field;
                    return;
                }
                if (value !== '' && field.type === 'number') {
                    var num = Number(value);
                    if (Number.isNaN(num) || num < 0) {
                        showError(field, 'Please enter a non-negative number.');
                        valid = false;
                        firstInvalid = firstInvalid || field;
                        return;
                    }
                }
                if (value !== '' && field.minLength > 0 && value.length < field.minLength) {
                    showError(field, 'Must be at least ' + field.minLength + ' characters.');
                    valid = false;
                    firstInvalid = firstInvalid || field;
                }
            });

            /* Date range validation: [data-date-from] must be <= [data-date-to] */
            var from = form.querySelector('[data-date-from]');
            var to   = form.querySelector('[data-date-to]');
            if (from && to && from.value && to.value && to.value < from.value) {
                showError(to, 'End date cannot be before the start date.');
                valid = false;
                firstInvalid = firstInvalid || to;
            }

            if (firstInvalid) firstInvalid.focus();
            return valid;
        }

        document.querySelectorAll('form[data-validate], form#loginForm').forEach(function (form) {
            form.setAttribute('novalidate', 'novalidate');
            form.addEventListener('submit', function (ev) {
                if (!validateForm(form)) {
                    ev.preventDefault();
                }
            });
        });
    });
})();
