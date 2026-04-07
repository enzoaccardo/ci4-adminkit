// Cron builder: inizializza OGNI contenitore .cron-builder presente in pagina.
// Ogni contenitore è indipendente (scoping interno per classe/data-part) e
// scrive l'espressione cron nell'input hidden indicato da data-target.
// Auto-init: usato come widget del form builder (tipo campo 'cron').
(function () {
    var PARTS = ['min', 'hour', 'day', 'month', 'weekday'];

    function initBuilder(builder) {
        var target  = document.getElementById(builder.dataset.target);
        if (!target) return;

        var preview = builder.querySelector('.cron-preview');
        var selects = {};
        PARTS.forEach(function (p) {
            selects[p] = builder.querySelector('.cron-select[data-part="' + p + '"]');
        });

        function selectVal(sel, val) {
            for (var i = 0; i < sel.options.length; i++) {
                if (sel.options[i].value === val) { sel.selectedIndex = i; return; }
            }
            sel.selectedIndex = 0; // fallback a '*'
        }

        function highlight(expr) {
            builder.querySelectorAll('.cron-preset').forEach(function (btn) {
                btn.classList.toggle('active', btn.dataset.cron === expr);
            });
        }

        function compose() {
            var expr = PARTS.map(function (p) { return selects[p].value; }).join(' ');
            if (preview) preview.textContent = expr;
            target.value = expr;
            highlight(expr);
        }

        function applyExpr(expr) {
            var parts = (expr || '').trim().split(/\s+/);
            if (parts.length !== 5) return;
            PARTS.forEach(function (p, i) { selectVal(selects[p], parts[i]); });
            compose();
        }

        builder.querySelectorAll('.cron-select').forEach(function (sel) {
            sel.addEventListener('change', compose);
        });
        builder.querySelectorAll('.cron-preset').forEach(function (btn) {
            btn.addEventListener('click', function () { applyExpr(btn.dataset.cron); });
        });

        applyExpr(builder.dataset.cron || '*/5 * * * *');
    }

    function initAll() {
        document.querySelectorAll('.cron-builder').forEach(initBuilder);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAll);
    } else {
        initAll();
    }
})();
