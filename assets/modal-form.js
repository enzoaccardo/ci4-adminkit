// modal-form.js — "crea nuovo" in modale che rientra nel form padre.
//
// Un bottone [data-create-new] (reso dal form builder accanto a un select con
// 'createNew') apre una modale, carica via AJAX il fragment del form (endpoint
// che restituisce JSON {html, css[], js[], init}), lo aggancia (bindForms) e, al
// submit AJAX andato a buon fine, inietta il record creato nel select padre
// (Tom Select aware), seleziona e chiude la modale — senza ricaricare il padre.
(function () {
    window.AdminKit = window.AdminKit || {};

    // (Ri)aggancia ajax + logic su un sotto-albero (widget: via init del fragment).
    AdminKit.bindForms = function (root) {
        if (AdminKit.bindAjax) AdminKit.bindAjax(root);
        if (AdminKit.bindLogic) AdminKit.bindLogic(root);
    };

    function toast(msg, type) {
        if (window.App && typeof App.toast === 'function') App.toast(msg, type);
    }

    // Carica gli asset del fragment non ancora presenti; risolve quando i JS sono pronti.
    function ensureAssets(css, js) {
        (css || []).forEach(function (href) {
            if (![].some.call(document.querySelectorAll('link[rel="stylesheet"]'), function (l) { return l.href === href || l.getAttribute('href') === href; })) {
                var link = document.createElement('link');
                link.rel = 'stylesheet'; link.href = href;
                document.head.appendChild(link);
            }
        });
        return (js || []).reduce(function (chain, src) {
            return chain.then(function () {
                var exists = [].some.call(document.querySelectorAll('script[src]'), function (s) { return s.src === src || s.getAttribute('src') === src; });
                if (exists) return Promise.resolve();
                return new Promise(function (resolve) {
                    var s = document.createElement('script');
                    s.src = src; s.onload = resolve; s.onerror = resolve;
                    document.body.appendChild(s);
                });
            });
        }, Promise.resolve());
    }

    // Modale riutilizzabile (una sola nel DOM). Show/hide autonomi: non dipende
    // da window.bootstrap (Bootstrap ESM non è sempre esposto globalmente).
    function modalEl() {
        var el = document.getElementById('akModalForm');
        if (el) return el;
        el = document.createElement('div');
        el.id = 'akModalForm';
        el.className = 'modal fade';
        el.tabIndex = -1;
        el.innerHTML =
            '<div class="modal-dialog modal-lg modal-dialog-centered"><div class="modal-content">'
            + '<div class="modal-header"><h5 class="modal-title"></h5>'
            + '<button type="button" class="btn-close" data-ak-dismiss aria-label="Chiudi"></button></div>'
            + '<div class="modal-body"></div></div></div>';
        document.body.appendChild(el);

        el.addEventListener('click', function (ev) {
            if (ev.target === el || (ev.target.closest && ev.target.closest('[data-ak-dismiss]'))) hideModal(el);
        });
        document.addEventListener('keydown', function (ev) {
            if (ev.key === 'Escape' && el.classList.contains('show')) hideModal(el);
        });
        return el;
    }

    function backdrop() {
        var b = document.getElementById('akModalBackdrop');
        if (!b) {
            b = document.createElement('div');
            b.id = 'akModalBackdrop';
            b.className = 'modal-backdrop fade';
            document.body.appendChild(b);
        }
        return b;
    }

    function showModal(el) {
        var b = backdrop();
        el.style.display = 'block';
        document.body.classList.add('modal-open');
        // forza reflow prima di aggiungere .show per la transizione fade
        void el.offsetWidth;
        el.classList.add('show');
        b.classList.add('show');
    }

    function hideModal(el) {
        el.classList.remove('show');
        el.style.display = 'none';
        document.body.classList.remove('modal-open');
        var b = document.getElementById('akModalBackdrop');
        if (b) b.parentNode.removeChild(b);
    }

    // Aggiunge/seleziona un'opzione in un select (Tom Select aware).
    function injectOption(select, rec) {
        if (!select || !rec) return;
        var value = String(rec.value), label = String(rec.label);
        var ts = select.tomselect;
        if (ts) {
            ts.addOption({ value: value, text: label });
            ts.refreshOptions(false);
            ts.addItem(value, true);
            return;
        }
        var op = document.createElement('option');
        op.value = value; op.textContent = label; op.selected = true;
        select.appendChild(op);
        select.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function openCreate(btn) {
        var url    = btn.getAttribute('data-url');
        var target = btn.getAttribute('data-target'); // id del select padre
        var title  = btn.getAttribute('data-title') || 'Nuovo';
        if (!url) return;

        var el = modalEl();
        var titleEl = el.querySelector('.modal-title');
        var body    = el.querySelector('.modal-body');
        titleEl.textContent = title;
        body.innerHTML = '<div class="text-center text-body-secondary py-4">Caricamento…</div>';

        showModal(el);

        fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest', 'Accept': 'application/json' }, credentials: 'same-origin' })
            .then(function (r) { return r.json(); })
            .then(function (frag) {
                return ensureAssets(frag.css, frag.js).then(function () {
                    body.innerHTML = frag.html || '';
                    if (frag.init) { try { (new Function(frag.init))(); } catch (e) { /* noop */ } }
                    AdminKit.bindForms(body);

                    var form = body.querySelector('form');
                    if (form) {
                        form.__akOnSuccess = function (j) {
                            var select = document.getElementById(target);
                            if (j.record) injectOption(select, j.record);
                            hideModal(el);
                            toast(j.message || 'Creato.', 'success');
                        };
                    }
                });
            })
            .catch(function () {
                body.innerHTML = '<div class="alert alert-danger">Errore nel caricamento del form.</div>';
            });
    }

    document.addEventListener('click', function (ev) {
        var btn = ev.target.closest ? ev.target.closest('[data-create-new]') : null;
        if (btn) { ev.preventDefault(); openCreate(btn); }
    });
})();
