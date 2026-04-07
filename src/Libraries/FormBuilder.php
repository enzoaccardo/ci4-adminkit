<?php

namespace AdminKit\Libraries;

use CodeIgniter\Model;

/**
 * Costruttore di form dichiarativi.
 *
 * Prende una configurazione (sezioni → campi) e produce una struttura
 * normalizzata che il partial Smarty `_partials/form.tpl` renderizza senza
 * logica. È il gemello, lato form, dei trait list-view (HasFilters/Sorting).
 *
 * Per ogni campo risolve:
 *  - il valore: old input → entità → default
 *  - l'errore di validazione (da session('errors'))
 *  - required/maxlength/minlength dedotti dai $validationRules del model (se passato),
 *    con override esplicito dalla config del campo
 *  - le opzioni (select/radio) con lo stato "selected"
 *
 * Tipi supportati: text, email, password, number, url, tel, date, datetime,
 * textarea, select, multiselect, checkbox, switch, radio, static, hidden, file, custom.
 */
class FormBuilder
{
    /** @var array<string,mixed> Old input POST della request precedente */
    private array $old = [];

    /** @var array<string,string> Errori di validazione per campo */
    private array $errors = [];

    /** @var string|null Base URL degli option provider (per i campi remoti con 'provider') */
    private ?string $optionsBase = null;

    /**
     * Costruisce la struttura normalizzata del form.
     *
     * @param array       $config Configurazione del form (action, sections, ...)
     * @param object|null $entity Entità per popolare i valori (o null in creazione)
     * @param Model|null  $model  Model per dedurre le regole (required/maxlength)
     * @return array Struttura pronta per il partial Smarty
     */
    public function build(array $config, ?object $entity = null, ?Model $model = null, ?string $optionsBase = null): array
    {
        $this->old         = session('_ci_old_input')['post'] ?? [];
        $this->errors      = session('errors') ?? [];
        $this->optionsBase = $optionsBase;

        $rules = $model ? $this->modelRules($model) : [];

        $multipart = false;
        $sections  = [];

        foreach ($config['sections'] ?? [] as $section) {
            $fields = [];
            foreach ($section['fields'] ?? [] as $name => $cfg) {
                $field = $this->buildField((string) $name, $cfg, $entity, $rules[$name] ?? null);
                if ($field['type'] === 'file') {
                    $multipart = true;
                }
                $fields[] = $field;
            }
            $sections[] = [
                'title'  => $section['title'] ?? null,
                'fields' => $fields,
            ];
        }

        $logic = $this->extractLogic($config);

        return [
            'action'      => $config['action'] ?? '',
            'method'      => $config['method'] ?? 'post',
            'multipart'   => $multipart,
            'sections'    => $sections,
            'submitLabel' => $config['submitLabel'] ?? 'Salva',
            'cancelUrl'   => $config['cancelUrl'] ?? null,
            'ajax'        => ! empty($config['ajax']),
            'logic'       => $logic,
            'logicJson'   => $logic === [] ? '' : json_encode($logic, JSON_HEX_APOS | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
        ];
    }

    /**
     * Estrae dalle 'affects' dei campi sorgente le regole di interazione,
     * normalizzate in un set piatto per il motore JS (form-logic.js).
     *
     * Regola `toggle`: quando il campo sorgente vale `when` (o è in `whenIn`),
     * applica gli effetti (show/require/enable) ai campi bersaglio; altrimenti
     * lo stato base (hide/optional/disable).
     * Regola `setOptions`: al change del sorgente ripopola via AJAX il bersaglio.
     *
     * @return array<int,array<string,mixed>>
     */
    private function extractLogic(array $config): array
    {
        $rules = [];

        foreach ($config['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $name => $cfg) {
                // Dichiarazione lato-bersaglio: il campo remoto dichiara da chi dipende
                // e il provider (URL costruito per convenzione) oppure una url esplicita.
                if (isset($cfg['remote'])) {
                    $r   = $cfg['remote'];
                    $url = $r['url'] ?? ($this->optionsBase !== null && ! empty($r['provider'])
                        ? $this->optionsBase . '/' . $r['provider']
                        : '');
                    if ($url !== '') {
                        $rules[] = [
                            'type'   => 'setOptions',
                            'source' => (string) ($r['dependsOn'] ?? ''),
                            'target' => (string) $name,
                            'url'    => $url,
                            'param'  => (string) ($r['param'] ?? $r['dependsOn'] ?? $name),
                        ];
                    }
                }

                foreach ($cfg['affects'] ?? [] as $affect) {
                    if (isset($affect['setOptions'])) {
                        foreach ($affect['setOptions'] as $target => $conf) {
                            $rules[] = [
                                'type'   => 'setOptions',
                                'source' => (string) $name,
                                'target' => (string) $target,
                                'url'    => $conf['url'] ?? '',
                                'param'  => $conf['param'] ?? (string) $name,
                            ];
                        }
                        continue;
                    }

                    $effects = [];
                    foreach (['show', 'require', 'enable'] as $eff) {
                        if (! empty($affect[$eff])) {
                            $effects[$eff] = array_values((array) $affect[$eff]);
                        }
                    }
                    if ($effects === []) {
                        continue;
                    }

                    $rules[] = [
                        'type'    => 'toggle',
                        'source'  => (string) $name,
                        'when'    => $affect['when']   ?? null,
                        'whenIn'  => isset($affect['whenIn']) ? array_values((array) $affect['whenIn']) : null,
                        'effects' => $effects,
                    ];
                }
            }
        }

        return $rules;
    }

    /**
     * Normalizza un singolo campo in un descrittore pronto per il template.
     *
     * @param array $inferred Regole dedotte dal model per questo campo
     */
    private function buildField(string $name, array $cfg, ?object $entity, ?array $inferred): array
    {
        $inferred = $inferred ?? ['required' => false, 'maxlength' => null, 'minlength' => null, 'email' => false];

        $type = $cfg['type'] ?? ($inferred['email'] ? 'email' : 'text');

        $required = $cfg['required'] ?? $inferred['required'];

        $attrs = $cfg['attrs'] ?? [];
        if (($inferred['maxlength'] ?? null) !== null && ! isset($attrs['maxlength'])) {
            $attrs['maxlength'] = $inferred['maxlength'];
        }
        if (($inferred['minlength'] ?? null) !== null && ! isset($attrs['minlength'])) {
            $attrs['minlength'] = $inferred['minlength'];
        }

        $value = $this->resolveValue($name, $type, $cfg, $entity);

        $field = [
            'name'          => $name,
            'type'          => $type,
            'partial'       => $this->partialFor($type),
            'tpl'           => 'layout/_partials/fields/' . $this->partialFor($type) . '.tpl',
            'label'         => $cfg['label'] ?? ucfirst(str_replace('_', ' ', $name)),
            'value'         => is_scalar($value) ? (string) $value : '',
            'required'      => (bool) $required,
            'hint'          => $cfg['hint'] ?? null,
            'col'           => $cfg['col'] ?? 12,
            'error'         => $this->errors[$name] ?? null,
            'attrs'         => $attrs,
            'html'          => $cfg['html'] ?? '',
            // Widget JS opzionale (es. 'tomselect'): asset e init sono gestiti
            // da HasForm/FieldWidgets, agganciati all'id univoco del campo.
            'widget'        => $cfg['widget'] ?? null,
            'widgetOptions' => $cfg['widgetOptions'] ?? [],
        ];

        if (in_array($type, ['checkbox', 'switch'], true)) {
            $field['checked'] = (bool) $value;
        }

        if (in_array($type, ['select', 'multiselect', 'radio'], true)) {
            $field['empty']   = $cfg['empty'] ?? null;
            $field['options'] = $this->buildOptions($cfg, $value, $type === 'multiselect');
        }

        return $field;
    }

    /**
     * Mappa il tipo di campo al partial di rendering (whitelist, fallback 'input').
     *
     * Più tipi condividono lo stesso partial (es. text/email/number → input):
     * la granularità è per famiglia, non per singolo type.
     */
    private function partialFor(string $type): string
    {
        return match ($type) {
            'textarea'              => 'textarea',
            'select', 'multiselect' => 'select',
            'checkbox', 'switch'    => 'check',
            'radio'                 => 'radio',
            'cron'                  => 'cron',
            'static'                => 'static',
            'custom'                => 'custom',
            'hidden'                => 'hidden',
            default                 => 'input',
        };
    }

    /**
     * Risolve il valore del campo: old input → valore esplicito → entità → default.
     *
     * @return mixed
     */
    private function resolveValue(string $name, string $type, array $cfg, ?object $entity)
    {
        // password/file non vengono mai pre-popolati
        if (in_array($type, ['password', 'file'], true)) {
            return '';
        }

        if (array_key_exists($name, $this->old)) {
            return $this->old[$name];
        }

        if (array_key_exists('value', $cfg)) {
            return $cfg['value'];
        }

        if ($entity !== null && isset($entity->{$name})) {
            return $entity->{$name};
        }

        return $cfg['default'] ?? null;
    }

    /**
     * Costruisce le opzioni di select/radio con lo stato selected.
     *
     * Le opzioni possono essere: mappa [value => label] oppure lista di
     * oggetti/array; in tal caso 'optionValue'/'optionLabel' indicano le chiavi
     * (default 'id'/'name').
     *
     * @param mixed $current Valore corrente (scalare o array per multiselect)
     * @return array<int,array{value:string,label:string,selected:bool}>
     */
    private function buildOptions(array $cfg, $current, bool $multi): array
    {
        $raw       = $cfg['options'] ?? [];
        $valueKey  = $cfg['optionValue'] ?? 'id';
        $labelKey  = $cfg['optionLabel'] ?? 'name';
        $selected  = $multi ? (array) ($current ?? []) : [$current];
        $selected  = array_map(static fn ($v) => (string) $v, $selected);

        $out = [];
        foreach ($raw as $key => $item) {
            if (is_object($item) || is_array($item)) {
                $obj   = (object) $item;
                $value = (string) ($obj->{$valueKey} ?? '');
                $label = (string) ($obj->{$labelKey} ?? '');
            } else {
                $value = (string) $key;
                $label = (string) $item;
            }

            $out[] = [
                'value'    => $value,
                'label'    => $label,
                'selected' => in_array($value, $selected, true),
            ];
        }

        return $out;
    }

    /**
     * Estrae dai $validationRules del model le informazioni utili al form.
     *
     * @return array<string,array{required:bool,maxlength:?int,minlength:?int,email:bool}>
     */
    private function modelRules(Model $model): array
    {
        $rules = [];

        foreach ($model->getValidationRules() as $field => $def) {
            $ruleStr = is_array($def) ? ($def['rules'] ?? '') : $def;
            if (is_array($ruleStr)) {
                $ruleStr = implode('|', $ruleStr);
            }

            $rules[$field] = [
                'required'  => str_contains($ruleStr, 'required') && ! str_contains($ruleStr, 'permit_empty'),
                'maxlength' => $this->ruleArg($ruleStr, 'max_length'),
                'minlength' => $this->ruleArg($ruleStr, 'min_length'),
                'email'     => str_contains($ruleStr, 'valid_email'),
            ];
        }

        return $rules;
    }

    /**
     * Estrae l'argomento numerico di una regola tipo `max_length[200]`.
     */
    private function ruleArg(string $rules, string $rule): ?int
    {
        if (preg_match('/' . preg_quote($rule, '/') . '\[(\d+)\]/', $rules, $m)) {
            return (int) $m[1];
        }

        return null;
    }
}
