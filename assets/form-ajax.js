// form-ajax.js — submit AJAX dei form del form builder (data-ajax).
// Idempotente e ri-agganciabile: bind via AdminKit.bindAjax(root) (usato anche
// per i form iniettati dinamicamente, es. dentro una modale). Un form può avere
// un hook di successo form.__akOnSuccess(json) (usato dalla modale) che sostituisce
// il comportamento di default (redirect). Risposta JSON {ok,message,redirect,errors,csrf,record}.
// Richiede App.toast (confirm-toast.js).
(function () {
    function clearErrors(form) {
        form.querySelectorAll('.is-invalid').forEach(function (e) { e.classList.remove('is-invalid'); });
        form.querySelectorAll('.ajax-error').forEach(function (e) { e.remove(); });
    }
    function showErrors(form, errors) {
        Object.keys(errors || {}).forEach(function (name) {
            var ctrl = form.querySelector('#f_' + name) || form.querySelector('[name="' + name + '"]');
            var wrap = form.querySelector('[data-field="' + name + '"]') || (ctrl && ctrl.closest('[data-field]'));
            if (ctrl) ctrl.classList.add('is-invalid');
            if (wrap) {
                var fb = document.createElement('div');
                fb.className = 'invalid-feedback d-block ajax-error';
                fb.textContent = errors[name];
                wrap.appendChild(fb);
            }
        });
    }
    function updateCsrf(form, token) {
        if (!token) return;
        var name = (window.__CSRF__ && window.__CSRF__.name) ? window.__CSRF__.name : 'csrf_token';
        if (window.__CSRF__) window.__CSRF__.value = token;
        // aggiorna TUTTI i campi CSRF della pagina (token per-sessione)
        document.querySelectorAll('input[name="' + name + '"]').forEach(function (i) { i.value = token; });
    }
    function toast(msg, type) {
        if (window.App && typeof App.toast === 'function') App.toast(msg, type);
    }
    function handle(form) {
        form.addEventListener('submit', function (ev) {
            ev.preventDefault();
            clearErrors(form);
            var btn = form.querySelector('[type="submit"]');
            if (btn) btn.disabled = true;
            fetch(form.action, {
                method: (form.getAttribute('method') || 'post').toUpperCase(),
                body: new FormData(form),
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' },
                credentials: 'same-origin'
            })
                .then(function (res) {
                    return res.json().then(function (json) { return { status: res.status, json: json }; });
                })
                .then(function (r) {
                    var j = r.json || {};
                    updateCsrf(form, j.csrf);
                    if (r.status >= 200 && r.status < 300 && j.ok) {
                        // hook di successo (es. modale: inietta il record nel form padre)
                        if (typeof form.__akOnSuccess === 'function') { form.__akOnSuccess(j); return; }
                        toast(j.message || 'Salvato.', 'success');
                        if (j.redirect) { window.location.href = j.redirect; return; }
                    } else {
                        showErrors(form, j.errors);
                        toast(j.message || 'Errore di validazione.', 'danger');
                    }
                })
                .catch(function () { toast('Errore di rete o risposta non valida.', 'danger'); })
                .finally(function () { if (btn) btn.disabled = false; });
        });
    }

    // Bind idempotente su un sotto-albero (default: document).
    function bind(root) {
        (root || document).querySelectorAll('form[data-ajax]').forEach(function (f) {
            if (f.__akAjaxBound) return;
            f.__akAjaxBound = true;
            handle(f);
        });
    }

    window.AdminKit = window.AdminKit || {};
    window.AdminKit.bindAjax = bind;

    if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', function () { bind(document); });
    else bind(document);
})();
