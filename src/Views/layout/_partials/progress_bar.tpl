{*
 * Barra di avanzamento riutilizzabile Bootstrap.
 *
 * Parametri:
 *  - progressId    (string) ID base per i sotto-elementi (default: 'progress')
 *  - progressLabel (string) Testo iniziale del label (default: 'In corso...')
 *
 * Genera gli ID:
 *  - {progressId}          → div contenitore (nascosto per default)
 *  - {progressId}-label    → span testo descrittivo
 *  - {progressId}-pct      → span percentuale
 *  - {progressId}-bar      → div progress-bar Bootstrap
 *}
<div id="{$progressId|default:'progress'}" class="mt-3" style="display:none">
    <div class="d-flex justify-content-between small text-muted mb-1">
        <span id="{$progressId|default:'progress'}-label">{$progressLabel|default:'In corso...'}</span>
        <span id="{$progressId|default:'progress'}-pct">0%</span>
    </div>
    <div class="progress" style="height: 8px">
        <div class="progress-bar progress-bar-striped progress-bar-animated"
             id="{$progressId|default:'progress'}-bar"
             role="progressbar"
             style="width: 0%"
             aria-valuenow="0"
             aria-valuemin="0"
             aria-valuemax="100"></div>
    </div>
</div>