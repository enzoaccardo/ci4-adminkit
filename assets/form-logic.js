// form-logic.js — motore di interazioni tra campi del form builder.
// Legge le regole da data-logic sul <form> e le applica su load + ad ogni change
// dei campi sorgente. Effetti: show/hide, enable/disable, required condizionale,
// setOptions (cascata via AJAX, con supporto Tom Select).
(function () {
    function wrapper(form, name) {
        return form.querySelector('[data-field="' + name + '"]');
    }

    function controls(form, name) {
        var w = wrapper(form, name);
        return w ? Array.prototype.slice.call(w.querySelectorAll('input, select, textarea')) : [];
    }

    // Valore corrente di un campo (per nome). Checkbox → booleano.
    function getValue(form, name) {
        var radios = form.querySelectorAll('input[type="radio"][name="' + name + '"]');
        if (radios.length) {
            for (var i = 0; i < radios.length; i++) {
                if (radios[i].checked) return radios[i].value;
            }
            return '';
        }
        var el = form.querySelector('[name="' + name + '"]');
        if (!el) return '';
        if (el.type === 'checkbox') return el.checked;
        return el.value;
    }

    function conditionActive(form, rule) {
        var v = getValue(form, rule.source);
        if (rule.whenIn) {
            return rule.whenIn.map(String).indexOf(String(v)) !== -1;
        }
        if (rule.when === null || rule.when === undefined) return true;
        if (typeof v === 'boolean') {
            return v === (rule.when === true || rule.when === 'true' || rule.when === 1 || rule.when === '1');
        }
        return String(v) === String(rule.when);
    }

    function initForm(form) {
        var rules;
        try { rules = JSON.parse(form.dataset.logic); } catch (e) { return; }
        if (!Array.isArray(rules) || !rules.length) return;

        var toggles = rules.filter(function (r) { return r.type === 'toggle'; });
        var remotes = rules.filter(function (r) { return r.type === 'setOptions'; });

        // ---- toggle: show / enable / require ----
        var controlled = { show: {}, enable: {}, require: {} };
        toggles.forEach(function (r) {
            Object.keys(r.effects).forEach(function (eff) {
                r.effects[eff].forEach(function (t) { controlled[eff][t] = true; });
            });
        });

        // Memorizza lo stato `required` statico iniziale di ogni controllo,
        // per poterlo ripristinare quando un campo torna visibile (vedi show).
        Object.keys(controlled.show).forEach(function (t) {
            controls(form, t).forEach(function (c) {
                if (c.dataset.req0 === undefined) c.dataset.req0 = c.required ? '1' : '0';
            });
        });

        function markRequired(form, t, req) {
            var w = wrapper(form, t); if (!w) return;
            var label = w.querySelector('label'); if (!label) return;
            var star = label.querySelector('.req-star');
            if (req && !star) {
                star = document.createElement('span');
                star.className = 'req-star text-danger';
                star.textContent = ' *';
                label.appendChild(star);
            } else if (!req && star) {
                star.remove();
            }
        }

        function applyToggles() {
            var on = { show: {}, enable: {}, require: {} };
            toggles.forEach(function (r) {
                if (!conditionActive(form, r)) return;
                Object.keys(r.effects).forEach(function (eff) {
                    r.effects[eff].forEach(function (t) { on[eff][t] = true; });
                });
            });

            Object.keys(controlled.show).forEach(function (t) {
                var w = wrapper(form, t); if (!w) return;
                var vis = !!on.show[t];
                w.style.display = vis ? '' : 'none';
                controls(form, t).forEach(function (c) {
                    // nascosto: niente required (non deve bloccare il submit);
                    // visibile: ripristina il required statico iniziale.
                    c.required = vis && c.dataset.req0 === '1';
                });
            });
            Object.keys(controlled.enable).forEach(function (t) {
                var en = !!on.enable[t];
                controls(form, t).forEach(function (c) { c.disabled = !en; });
            });
            Object.keys(controlled.require).forEach(function (t) {
                var req = !!on.require[t];
                // applica required solo se il campo è visibile (o non controllato in visibilità)
                if (controlled.show[t] && !on.show[t]) req = false;
                controls(form, t).forEach(function (c) { c.required = req; });
                markRequired(form, t, req);
            });
        }

        var toggleSources = {};
        toggles.forEach(function (r) { toggleSources[r.source] = true; });
        Object.keys(toggleSources).forEach(function (name) {
            form.querySelectorAll('[name="' + name + '"]').forEach(function (el) {
                el.addEventListener('change', applyToggles);
            });
        });

        // ---- setOptions: cascata via AJAX ----
        function setOptions(select, opts, selected) {
            var ts = select.tomselect;
            if (ts) {
                ts.clear(true);
                ts.clearOptions();
                opts.forEach(function (o) { ts.addOption({ value: String(o.value), text: String(o.label) }); });
                ts.refreshOptions(false);
                if (selected) ts.setValue(String(selected), true);
                return;
            }
            var empty = select.querySelector('option[value=""]');
            select.innerHTML = '';
            if (empty) select.appendChild(empty);
            opts.forEach(function (o) {
                var op = document.createElement('option');
                op.value = String(o.value);
                op.textContent = String(o.label);
                if (selected && String(selected) === String(o.value)) op.selected = true;
                select.appendChild(op);
            });
        }

        remotes.forEach(function (r) {
            function reload(keepSelected) {
                var target = form.querySelector('#f_' + r.target);
                if (!target) return;
                var val = getValue(form, r.source);
                var selected = keepSelected ? target.dataset.value : '';
                if (val === '' || val === false || val === null) {
                    setOptions(target, [], '');
                    return;
                }
                var url = r.url + (r.url.indexOf('?') >= 0 ? '&' : '?')
                    + encodeURIComponent(r.param) + '=' + encodeURIComponent(val);
                fetch(url, { headers: { 'Accept': 'application/json' }, credentials: 'same-origin' })
                    .then(function (res) { return res.json(); })
                    .then(function (data) {
                        var opts = Array.isArray(data) ? data : (data.results || []);
                        setOptions(target, opts, selected);
                    })
                    .catch(function () {});
            }

            form.querySelectorAll('[name="' + r.source + '"]').forEach(function (el) {
                el.addEventListener('change', function () { reload(false); });
            });

            // primo caricamento in edit (sorgente già valorizzato): riseleziona il valore salvato.
            // Deferito così eventuali Tom Select sono già inizializzati.
            var initVal = getValue(form, r.source);
            if (initVal !== '' && initVal !== false) {
                setTimeout(function () { reload(true); }, 0);
            }
        });

        applyToggles();
    }

    function initAll() {
        document.querySelectorAll('form[data-logic]').forEach(initForm);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
