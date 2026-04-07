<?php

namespace AdminKit\Libraries;

/**
 * Registro dei widget JS per i campi del form builder.
 *
 * Ogni widget dichiara: gli asset CSS/JS della libreria (caricati UNA sola volta
 * dal controller anche con più campi, grazie alla deduplica di addCss/addJs) e
 * un generatore di init eseguito PER SINGOLO campo, agganciato al suo id
 * univoco `#f_{name}`. Così N campi dello stesso widget (es. più select con
 * ricerca) convivono senza conflitti nello stesso form.
 *
 * Per aggiungere un widget: una nuova voce in definition().
 */
class FieldWidgets
{
    /**
     * Restituisce la definizione di un widget, o null se sconosciuto.
     *
     * @return array{css: string[], js: string[], init: callable}|null
     */
    public static function definition(string $widget): ?array
    {
        $assets = base_url(config('AdminKit')->assetBase);
        $vendor = "{$assets}/vendors";

        return match ($widget) {
            // Tom Select: select avanzato (ricerca/tag/multi/remote) senza jQuery.
            'tomselect' => [
                'css'  => ["{$vendor}/tom-select.bootstrap5.min.css"],
                'js'   => ["{$vendor}/tom-select.complete.min.js"],
                'init' => static function (array $field): string {
                    $opts = json_encode(
                        (object) ($field['widgetOptions'] ?? []),
                        JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE
                    );

                    return sprintf(
                        "  if (document.getElementById('f_%s')) new TomSelect('#f_%s', %s);",
                        $field['name'],
                        $field['name'],
                        $opts
                    );
                },
            ],
            // Cron builder: costruttore visuale di espressione cron.
            // Auto-inizializza tutti i .cron-builder in pagina → nessun init per-campo.
            'cronbuilder' => [
                'css'  => [],
                'js'   => ["{$assets}/cron-builder.js"],
                'init' => null,
            ],

            // Flatpickr: date/datetime picker (jQuery-free).
            'flatpickr' => [
                'css'  => ["{$vendor}/flatpickr.min.css"],
                'js'   => ["{$vendor}/flatpickr.min.js"],
                'init' => static fn (array $f): string => sprintf(
                    "  if (document.getElementById('f_%s')) flatpickr('#f_%s', %s);",
                    $f['name'],
                    $f['name'],
                    json_encode((object) ($f['widgetOptions'] ?? []), JSON_UNESCAPED_SLASHES)
                ),
            ],

            // FilePond: input file ricco con anteprima/drag&drop (jQuery-free).
            'filepond' => [
                'css'  => ["{$vendor}/filepond.min.css"],
                'js'   => ["{$vendor}/filepond.min.js"],
                'init' => static fn (array $f): string => sprintf(
                    "  if (document.getElementById('f_%s')) FilePond.create(document.getElementById('f_%s'), %s);",
                    $f['name'],
                    $f['name'],
                    json_encode((object) ($f['widgetOptions'] ?? []), JSON_UNESCAPED_SLASHES)
                ),
            ],

            // Password strength meter (custom, nessuna dipendenza esterna).
            'pwstrength' => [
                'css'  => [],
                'js'   => ["{$assets}/pw-strength.js"],
                'init' => static fn (array $f): string => sprintf(
                    "  if (window.PwStrength) PwStrength.attach('#f_%s');",
                    $f['name']
                ),
            ],

            default => null,
        };
    }
}
