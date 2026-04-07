/**
 * App.toast  — sistema toast Bootstrap 5 (CSS-based, senza bootstrap.Toast JS)
 * App.confirm — modal di conferma con supporto passkey WebAuthn mid-session
 *
 * Utilizzo toast:
 *   App.toast('Operazione completata', 'success');
 *   App.toast('Errore', 'danger', 8000);
 *
 * Utilizzo confirm via HTML (zero JS aggiuntivo per lo sviluppatore):
 *   <form data-confirm="Eliminare questo record?"
 *         data-confirm-title="Elimina"
 *         data-confirm-btn="Elimina"
 *         data-confirm-btn-class="btn-danger"
 *         data-confirm-passkey="true">
 *
 * Utilizzo confirm programmatico:
 *   App.confirm({
 *     title: 'Titolo', body: 'Messaggio HTML', btnText: 'Ok', btnClass: 'btn-danger',
 *     passkey: true,
 *     onConfirm: function() { ... }
 *   });
 */
(function () {
    'use strict';

    // =========================================================================
    // Toast — CSS puro, non dipende da bootstrap.Toast JS
    // =========================================================================

    var TOAST_ICONS = {
        success: 'bi-check-circle-fill text-success',
        danger:  'bi-x-circle-fill text-danger',
        warning: 'bi-exclamation-triangle-fill text-warning',
        info:    'bi-info-circle-fill text-info',
    };

    /**
     * Mostra un toast nell'angolo in basso a destra.
     *
     * @param {string} message  Testo (HTML consentito)
     * @param {string} type     'success' | 'danger' | 'warning' | 'info'
     * @param {number} duration Millisecondi prima della chiusura automatica (default 5000)
     */
    function toast(message, type, duration) {
        type     = type     || 'success';
        duration = duration || 5000;

        var container = document.getElementById('app-toast-container');
        if (!container) return;

        var icon = TOAST_ICONS[type] || TOAST_ICONS.info;
        var el   = document.createElement('div');

        el.className = 'toast align-items-center border-0 shadow-sm mb-2';
        el.setAttribute('role', 'alert');
        el.style.cssText = 'transition:opacity .3s ease;opacity:0;';
        el.innerHTML =
            '<div class="d-flex">' +
                '<div class="toast-body d-flex align-items-start gap-2">' +
                    '<i class="bi ' + icon + ' flex-shrink-0 mt-1" style="font-size:1rem"></i>' +
                    '<span>' + message + '</span>' +
                '</div>' +
                '<button type="button" class="btn-close me-2 m-auto" aria-label="Chiudi"></button>' +
            '</div>';

        container.appendChild(el);

        // Fade-in
        requestAnimationFrame(function () {
            requestAnimationFrame(function () { el.style.opacity = '1'; });
        });

        // Chiusura manuale
        el.querySelector('.btn-close').addEventListener('click', function () { removeToast(el); });

        // Auto-dismiss
        var timer = setTimeout(function () { removeToast(el); }, duration);
        el.addEventListener('mouseenter', function () { clearTimeout(timer); });
        el.addEventListener('mouseleave', function () {
            timer = setTimeout(function () { removeToast(el); }, 2000);
        });
    }

    function removeToast(el) {
        el.style.opacity = '0';
        setTimeout(function () { if (el.parentNode) el.parentNode.removeChild(el); }, 300);
    }

    // =========================================================================
    // Confirm modal — stato centralizzato
    // =========================================================================

    // Callback corrente: impostata da openModal() o confirmCustom()
    var _onConfirm = null;

    /**
     * Apre il modal popolandolo e impostando il callback di conferma.
     *
     * @param {Object} opts
     * @param {string}   opts.title
     * @param {string}   opts.body
     * @param {string}   opts.btnText
     * @param {string}   opts.btnClass
     * @param {boolean}  opts.passkey    true = usa passkey se disponibile
     * @param {Function} opts.onConfirm  eseguita dopo conferma (semplice o passkey)
     */
    function openModal(opts) {
        _onConfirm = opts.onConfirm || null;

        document.getElementById('app-confirm-title').textContent = opts.title    || 'Conferma';
        document.getElementById('app-confirm-body').innerHTML    = opts.body     || 'Sei sicuro?';

        var confirmBtn = document.getElementById('app-confirm-btn');
        var passkeyBtn = document.getElementById('app-passkey-btn');
        var hasPasskey = !!(window.__APP__ && window.__APP__.hasPasskey);

        if (opts.passkey && hasPasskey) {
            confirmBtn.classList.add('d-none');
            passkeyBtn.classList.remove('d-none');
            passkeyBtn.disabled  = false;
            passkeyBtn.innerHTML = '<i class="bi bi-fingerprint me-1"></i>Verifica con passkey';
        } else {
            var btnText  = opts.btnText  || 'Conferma';
            var btnClass = opts.btnClass || 'btn-primary';
            confirmBtn.textContent = btnText;
            confirmBtn.className   = 'btn ' + btnClass;
            confirmBtn.classList.remove('d-none');
            passkeyBtn.classList.add('d-none');
        }

        showModal();
    }

    // =========================================================================
    // Apertura / chiusura modal senza bootstrap.Modal JS
    // Bootstrap è bundled in app.js e non è window.bootstrap:
    // usiamo la manipolazione diretta del DOM con le classi Bootstrap.
    // =========================================================================

    function showModal() {
        var modal = document.getElementById('appConfirmModal');
        if (!modal) return;

        // Backdrop
        var backdrop = document.createElement('div');
        backdrop.className = 'modal-backdrop fade show';
        backdrop.id        = 'app-modal-backdrop';
        document.body.appendChild(backdrop);

        document.body.classList.add('modal-open');
        modal.removeAttribute('aria-hidden');
        modal.setAttribute('aria-modal', 'true');

        // app-modal-open forza display:block via CSS dedicato nel layout
        // (immune da eventuali !important di AdminLTE/Bootstrap)
        modal.classList.add('app-modal-open');

        requestAnimationFrame(function () {
            requestAnimationFrame(function () { modal.classList.add('show'); });
        });
    }

    function hideModal() {
        var modal    = document.getElementById('appConfirmModal');
        var backdrop = document.getElementById('app-modal-backdrop');
        if (!modal) return;

        modal.classList.remove('show', 'app-modal-open');
        document.body.classList.remove('modal-open');
        modal.setAttribute('aria-hidden', 'true');
        modal.removeAttribute('aria-modal');

        if (backdrop) backdrop.remove();

        var passkeyBtn = document.getElementById('app-passkey-btn');
        if (passkeyBtn) {
            passkeyBtn.disabled  = false;
            passkeyBtn.innerHTML = '<i class="bi bi-fingerprint me-1"></i>Verifica con passkey';
        }

        _onConfirm = null;
    }

    // =========================================================================
    // Passkey WebAuthn mid-session
    // =========================================================================

    /**
     * Esegue il challenge WebAuthn e chiama onSuccess se verificato.
     * onSuccess viene chiamata DOPO la chiusura del modal.
     *
     * @param {Function} onSuccess
     */
    async function runPasskeyChallenge(onSuccess) {
        var passkeyBtn   = document.getElementById('app-passkey-btn');
        var originalHtml = passkeyBtn.innerHTML;

        passkeyBtn.disabled  = true;
        passkeyBtn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span>Verifica…';

        try {
            // 1 — Opzioni WebAuthn
            var optRes = await fetch(window.__APP__.passkeyOptionsUrl, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     window.__CSRF__.value,
                },
                body: '{}',
            });

            if (!optRes.ok) {
                var e1 = await optRes.json();
                throw new Error(e1.error || 'Errore nel recupero delle opzioni.');
            }

            var options = await optRes.json();
            if (options._csrf) { window.__CSRF__.value = options._csrf; delete options._csrf; }

            // 2 — Challenge browser (Touch ID / Face ID)
            var credential = await navigator.credentials.get({
                publicKey: prepareAssertionOptions(options),
            });

            if (!credential) throw new Error('Verifica annullata.');

            // 3 — Verifica sul server
            var payload = serializeCredential(credential);
            payload['_csrf'] = window.__CSRF__.value;

            var verRes = await fetch(window.__APP__.passkeyVerifyUrl, {
                method:  'POST',
                headers: {
                    'Content-Type':     'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN':     window.__CSRF__.value,
                },
                body: JSON.stringify(payload),
            });

            if (!verRes.ok) {
                var e2 = await verRes.json();
                throw new Error(e2.error || 'Verifica passkey fallita.');
            }

            var result = await verRes.json();

            // 4 — Aggiorna CSRF nel DOM
            if (result.csrf) {
                window.__CSRF__.value = result.csrf;
                document.querySelectorAll('input[name="' + window.__CSRF__.name + '"]')
                    .forEach(function (el) { el.value = result.csrf; });
            }

            // 5 — Chiude il modal e chiama il callback
            hideModal();
            setTimeout(onSuccess, 150);

        } catch (err) {
            passkeyBtn.disabled  = false;
            passkeyBtn.innerHTML = originalHtml;
            toast(err.message || 'Verifica passkey fallita.', 'danger');
        }
    }

    // =========================================================================
    // Helpers WebAuthn
    // =========================================================================

    function base64urlToBuffer(s) {
        var b = s.replace(/-/g, '+').replace(/_/g, '/');
        var bin = atob(b);
        var buf = new Uint8Array(bin.length);
        for (var i = 0; i < bin.length; i++) buf[i] = bin.charCodeAt(i);
        return buf.buffer;
    }

    function bufferToBase64url(buf) {
        var bytes = new Uint8Array(buf);
        var bin = '';
        for (var i = 0; i < bytes.byteLength; i++) bin += String.fromCharCode(bytes[i]);
        return btoa(bin).replace(/\+/g, '-').replace(/\//g, '_').replace(/=/g, '');
    }

    function prepareAssertionOptions(opts) {
        var pk = Object.assign({}, opts);
        pk.challenge = base64urlToBuffer(pk.challenge);
        if (pk.allowCredentials) {
            pk.allowCredentials = pk.allowCredentials.map(function (c) {
                return Object.assign({}, c, { id: base64urlToBuffer(c.id) });
            });
        }
        return pk;
    }

    function serializeCredential(cred) {
        return {
            id:    cred.id,
            rawId: bufferToBase64url(cred.rawId),
            type:  cred.type,
            response: {
                authenticatorData: bufferToBase64url(cred.response.authenticatorData),
                clientDataJSON:    bufferToBase64url(cred.response.clientDataJSON),
                signature:         bufferToBase64url(cred.response.signature),
                userHandle:        cred.response.userHandle
                    ? bufferToBase64url(cred.response.userHandle) : null,
            },
        };
    }

    // =========================================================================
    // Init — listener fissi su confirm/passkey button, intercettazione form
    // =========================================================================

    function init() {
        // Bottone "Conferma" — chiama sempre il callback corrente
        var confirmBtn = document.getElementById('app-confirm-btn');
        if (confirmBtn) {
            confirmBtn.addEventListener('click', function () {
                if (!_onConfirm) { hideModal(); return; }
                var cb = _onConfirm;
                hideModal();
                setTimeout(cb, 150);
            });
        }

        // Bottone "Verifica con passkey" — esegue il challenge poi chiama callback
        var passkeyBtn = document.getElementById('app-passkey-btn');
        if (passkeyBtn) {
            passkeyBtn.addEventListener('click', function () {
                if (!_onConfirm) { hideModal(); return; }
                var cb = _onConfirm;
                runPasskeyChallenge(cb);
            });
        }

        // Bottone "Annulla"
        var cancelBtn = document.getElementById('app-cancel-btn');
        if (cancelBtn) cancelBtn.addEventListener('click', hideModal);

        // Chiude il modal al click su btn-close e sul backdrop
        var modalEl = document.getElementById('appConfirmModal');
        if (modalEl) {
            modalEl.querySelector('.btn-close').addEventListener('click', hideModal);
            // Click fuori dal modal-dialog (backdrop area)
            modalEl.addEventListener('click', function (e) {
                if (e.target === modalEl) hideModal();
            });
        }

        // Tasto ESC chiude il modal
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && document.getElementById('appConfirmModal').classList.contains('show')) {
                hideModal();
            }
        });

        // Unico meccanismo di intercettazione — click su [data-confirm][data-post-url].
        //
        // Tutti i bottoni di azione usano type="button" + data-post-url: evita qualsiasi
        // problema con form annidati (browser HTML5 ignora il form interno nel DOM).
        // Il POST viene effettuato via form temporaneo per gestire redirect nativamente.
        //
        // Attributi supportati sul bottone:
        //   data-confirm          — testo del body del modal (obbligatorio)
        //   data-confirm-title    — titolo modal (default: 'Conferma')
        //   data-confirm-btn      — testo bottone conferma (default: 'Conferma')
        //   data-confirm-btn-class — classe CSS bottone (default: 'btn-primary')
        //   data-confirm-passkey  — 'true' per richiedere verifica passkey
        //   data-post-url         — URL a cui fare il POST (obbligatorio)
        //   data-post-params      — JSON con parametri extra da includere nel POST
        //   data-include          — selettore CSS per raccogliere input aggiuntivi
        //                          (ricercati dentro il più vicino [data-form-scope])
        document.addEventListener('click', function (e) {
            var btn = e.target.closest('[data-confirm][data-post-url]');
            if (!btn) return;
            e.preventDefault();
            e.stopImmediatePropagation();

            openModal({
                title:    btn.dataset.confirmTitle    || 'Conferma',
                body:     btn.dataset.confirm,
                btnText:  btn.dataset.confirmBtn      || 'Conferma',
                btnClass: btn.dataset.confirmBtnClass || 'btn-primary',
                passkey:  btn.dataset.confirmPasskey  === 'true',
                onConfirm: function () {
                    var f    = document.createElement('form');
                    f.method = 'POST';
                    f.action = btn.dataset.postUrl;

                    // CSRF
                    var csrf  = document.createElement('input');
                    csrf.type  = 'hidden';
                    csrf.name  = window.__CSRF__.name;
                    csrf.value = window.__CSRF__.value;
                    f.appendChild(csrf);

                    // Parametri fissi (es. type=all per cache flush)
                    if (btn.dataset.postParams) {
                        try {
                            var params = JSON.parse(btn.dataset.postParams);
                            Object.keys(params).forEach(function (k) {
                                var p  = document.createElement('input');
                                p.type  = 'hidden';
                                p.name  = k;
                                p.value = params[k];
                                f.appendChild(p);
                            });
                        } catch (_) {}
                    }

                    // Campi dinamici (es. totp_code per rigenera-recovery)
                    if (btn.dataset.include) {
                        var scope = btn.closest('[data-form-scope]') || btn.parentElement;
                        scope.querySelectorAll(btn.dataset.include).forEach(function (el) {
                            if (!el.name) return;
                            var x  = document.createElement('input');
                            x.type  = 'hidden';
                            x.name  = el.name;
                            x.value = el.value;
                            f.appendChild(x);
                        });
                    }

                    document.body.appendChild(f);
                    f.submit();
                },
            });
        }, true);
    }

    // =========================================================================
    // API pubblica
    // =========================================================================

    window.App         = window.App || {};
    window.App.toast   = toast;

    /**
     * Apre il modal di conferma programmaticamente.
     *
     * @param {Object}   options
     * @param {string}   options.title
     * @param {string}   options.body      HTML consentito
     * @param {string}   [options.btnText]
     * @param {string}   [options.btnClass]
     * @param {boolean}  [options.passkey]
     * @param {Function} options.onConfirm
     */
    window.App.confirm = function (options) { openModal(options); };

    document.addEventListener('DOMContentLoaded', init);

})();
