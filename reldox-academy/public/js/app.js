/**
 * RelDoxAcademy — App JS
 * Gestisce login, logout e interazioni UI base.
 */

(function () {
    'use strict';

    const R = window.RDACADEMY || {};

    /* ============================================================
       Utility
    ============================================================ */
    function ajax(action, data, callback) {
        const form = new FormData();
        form.append('action', 'rdacademy');
        form.append('nonce', R.nonce);
        form.append('rdacademy_action', action);

        for (const [k, v] of Object.entries(data || {})) {
            form.append(k, v);
        }

        fetch(R.ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
            .then(r => r.json())
            .then(callback)
            .catch(() => { const f = document.getElementById('rx-login-form'); callback({ success: false, data: (f && f.dataset.rete) || 'Errore di rete. Riprova.' }); });
    }

    function showError(el, msg) {
        if (!el) return;
        el.textContent = msg;
        el.style.display = 'block';
    }

    function hideError(el) {
        if (!el) return;
        el.style.display = 'none';
    }

    function setLoading(btn, loading) {
        if (!btn) return;
        const text   = btn.querySelector('.rx-btn-text');
        const loader = btn.querySelector('.rx-btn-loader');
        btn.disabled = loading;
        if (text)   text.style.display   = loading ? 'none'         : '';
        if (loader) loader.style.display = loading ? 'inline-flex'  : 'none';
    }

    /* ============================================================
       Login
    ============================================================ */
    function initLogin() {
        const form  = document.getElementById('rx-login-form');
        if (!form) return;

        const error  = document.getElementById('rx-login-error');
        const submit = document.getElementById('rx-login-submit');

        // Toggle password visibilità
        document.querySelectorAll('.rx-pwd-toggle').forEach(btn => {
            btn.addEventListener('click', function () {
                const target = document.getElementById(this.dataset.target);
                if (!target) return;
                const show = target.type === 'password';
                target.type = show ? 'text' : 'password';
                /* Le parole nella lingua della porta (data-mostra / data-nascondi), italiano se mancano. */
                this.textContent = show ? (this.dataset.nascondi || 'Nascondi') : (this.dataset.mostra || 'Mostra');
            });
        });

        form.addEventListener('submit', function (e) {
            e.preventDefault();
            hideError(error);

            const email    = (form.email.value || '').trim();
            const password = (form.password.value || '');

            if (!email || !password) {
                showError(error, form.dataset.vuoti || 'Inserisci email e password.');
                return;
            }

            setLoading(submit, true);
            entra({ email, password });
        });

        function entra(dati) {
            ajax('login', dati, function (res) {
                setLoading(submit, false);
                if (res.success) {
                    window.location.href = res.data.redirect;
                } else {
                    showError(error, (typeof res.data === 'string' && res.data) ? res.data : (form.dataset.nonValide || 'Credenziali non valide.'));
                    form.password.value = '';
                    form.password.focus();
                }
            });
        }
    }

    /* ============================================================
       Logout
    ============================================================ */
    function initLogout() {
        const btn = document.getElementById('rx-logout');
        if (!btn) return;

        btn.addEventListener('click', function () {
            ajax('logout', {}, function (res) {
                if (res.success) {
                    window.location.href = res.data.redirect;
                } else {
                    window.location.reload();
                }
            });
        });
    }

    /* ============================================================
       Righe tabella cliccabili
    ============================================================ */
    function initTableRows() {
        document.querySelectorAll('.rx-table-row-link').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('a') || e.target.closest('button')) return;
                const href = this.dataset.href;
                if (href) window.location.href = href;
            });
        });
    }
    /* ============================================================
       Init
    ============================================================ */
    document.addEventListener('DOMContentLoaded', function () {
        initLogin();
        initLogout();
        initTableRows();
    });

})();
