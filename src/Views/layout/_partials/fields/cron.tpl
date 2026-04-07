{* Campo cron: costruttore visuale di espressione cron. Il JS (widget cronbuilder,
   cron-builder.js) inizializza il contenitore .cron-builder e scrive nell'hidden
   #f_{name}. Value corrente in data-cron. *}
{assign var='cronVal' value=$f.value|default:'*/5 * * * *'}
<label class="form-label d-block">{$f.label}{if $f.required} <span class="text-danger">*</span>{/if}</label>

<div class="cron-builder border rounded p-3" data-cron="{$cronVal}" data-target="f_{$f.name}">

    <div class="mb-3">
        <label class="form-label text-muted small text-uppercase fw-semibold">Preset rapidi</label>
        <div class="d-flex flex-wrap gap-2">
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="* * * * *">Ogni minuto</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="*/5 * * * *">Ogni 5 min</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="*/15 * * * *">Ogni 15 min</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="*/30 * * * *">Ogni 30 min</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 * * * *">Ogni ora</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 */6 * * *">Ogni 6 ore</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 0 * * *">Ogni giorno (mezzanotte)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 2 * * *">Ogni giorno (02:00)</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 0 * * 1">Ogni lunedì</button>
            <button type="button" class="btn btn-sm btn-outline-secondary cron-preset" data-cron="0 0 1 * *">Ogni mese (1°)</button>
        </div>
    </div>

    <hr class="my-3">

    <div class="row g-3 mb-3">
        <div class="col">
            <label class="form-label small text-muted text-uppercase fw-semibold">Minuto</label>
            <select class="form-select form-select-sm cron-select" data-part="min">
                <option value="*">Ogni</option>
                <option value="*/2">ogni 2</option>
                <option value="*/5">ogni 5</option>
                <option value="*/10">ogni 10</option>
                <option value="*/15">ogni 15</option>
                <option value="*/20">ogni 20</option>
                <option value="*/30">ogni 30</option>
                {for $i=0 to 59}<option value="{$i}">{$i}</option>{/for}
            </select>
        </div>
        <div class="col">
            <label class="form-label small text-muted text-uppercase fw-semibold">Ora</label>
            <select class="form-select form-select-sm cron-select" data-part="hour">
                <option value="*">Ogni</option>
                <option value="*/2">ogni 2</option>
                <option value="*/4">ogni 4</option>
                <option value="*/6">ogni 6</option>
                <option value="*/8">ogni 8</option>
                <option value="*/12">ogni 12</option>
                {for $i=0 to 23}<option value="{$i}">{$i}</option>{/for}
            </select>
        </div>
        <div class="col">
            <label class="form-label small text-muted text-uppercase fw-semibold">Giorno</label>
            <select class="form-select form-select-sm cron-select" data-part="day">
                <option value="*">Ogni</option>
                {for $i=1 to 31}<option value="{$i}">{$i}</option>{/for}
            </select>
        </div>
        <div class="col">
            <label class="form-label small text-muted text-uppercase fw-semibold">Mese</label>
            <select class="form-select form-select-sm cron-select" data-part="month">
                <option value="*">Ogni</option>
                <option value="1">Gen</option><option value="2">Feb</option><option value="3">Mar</option>
                <option value="4">Apr</option><option value="5">Mag</option><option value="6">Giu</option>
                <option value="7">Lug</option><option value="8">Ago</option><option value="9">Set</option>
                <option value="10">Ott</option><option value="11">Nov</option><option value="12">Dic</option>
            </select>
        </div>
        <div class="col">
            <label class="form-label small text-muted text-uppercase fw-semibold">Giorno sett.</label>
            <select class="form-select form-select-sm cron-select" data-part="weekday">
                <option value="*">Ogni</option>
                <option value="1">Lun</option><option value="2">Mar</option><option value="3">Mer</option>
                <option value="4">Gio</option><option value="5">Ven</option><option value="6">Sab</option>
                <option value="0">Dom</option>
            </select>
        </div>
    </div>

    <div class="d-flex align-items-center gap-2 p-2 bg-body-secondary rounded">
        <span class="text-muted small">Espressione:</span>
        <code class="cron-preview fs-6"></code>
    </div>

    <input type="hidden" name="{$f.name}" id="f_{$f.name}" value="{$cronVal}">
</div>
{if $f.error}<div class="text-danger small mt-1">{$f.error}</div>{/if}
{if $f.hint}<div class="form-text">{$f.hint}</div>{/if}
