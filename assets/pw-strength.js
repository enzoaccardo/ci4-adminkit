// pw-strength.js — indicatore di robustezza password (widget pwstrength).
// Nessuna dipendenza. Uso: PwStrength.attach('#f_password').
(function () {
    var LABELS = ['Molto debole', 'Debole', 'Discreta', 'Buona', 'Forte'];
    var COLORS = ['bg-danger', 'bg-danger', 'bg-warning', 'bg-info', 'bg-success'];

    function score(v) {
        if (!v) return 0;
        var s = 0;
        if (v.length >= 8) s++;
        if (v.length >= 12) s++;
        if (/[a-z]/.test(v) && /[A-Z]/.test(v)) s++;
        if (/\d/.test(v)) s++;
        if (/[^A-Za-z0-9]/.test(v)) s++;
        return Math.min(s, 4); // 0..4
    }

    function attach(selector) {
        var input = document.querySelector(selector);
        if (!input || input.dataset.pwsInit) return;
        input.dataset.pwsInit = '1';

        var wrap = document.createElement('div');
        wrap.className = 'mt-1';
        wrap.innerHTML = '<div class="progress" style="height:6px"><div class="progress-bar" role="progressbar" style="width:0%"></div></div>'
            + '<small class="text-muted pws-label"></small>';
        input.insertAdjacentElement('afterend', wrap);

        var bar = wrap.querySelector('.progress-bar');
        var lbl = wrap.querySelector('.pws-label');

        function update() {
            var v = input.value;
            var sc = score(v);
            bar.className = 'progress-bar ' + (v ? COLORS[sc] : '');
            bar.style.width = (v ? ((sc + 1) / 5 * 100) : 0) + '%';
            lbl.textContent = v ? LABELS[sc] : '';
        }

        input.addEventListener('input', update);
        update();
    }

    window.PwStrength = { attach: attach };
})();
