<?php

namespace AdminKit\Traits;

use AdminKit\Libraries\FieldWidgets;
use AdminKit\Libraries\FormBuilder;
use CodeIgniter\Model;

/**
 * Trait per i controller admin: costruisce un form dichiarativo e lo assegna
 * alla view. Il template renderizza con {include file='layout/_partials/form.tpl'}.
 *
 * Richiede che il controller esponga assign() (AdminController via Smarty).
 */
trait HasForm
{
    /**
     * Normalizza la config del form e la assegna alla view come `$form`.
     *
     * @param array       $config Configurazione (action, sections, submitLabel, cancelUrl)
     * @param object|null $entity Entità per popolare i valori (null in creazione)
     * @param Model|null  $model  Model per dedurre required/maxlength dai validationRules
     * @return void
     */
    protected function buildForm(array $config, ?object $entity = null, ?Model $model = null): void
    {
        $form = (new FormBuilder())->build($config, $entity, $model, $this->formOptionsBase());

        $this->registerWidgets($form);

        // Motore di interazioni tra campi (show/hide, cascata AJAX, ...): caricato
        // solo se il form dichiara regole (campi con 'affects').
        if (! empty($form['logic'])) {
            $this->addJs(base_url('themes/admin/default/assets/form-logic.js'));
        }

        // Submit AJAX (senza reload) se il form è opt-in con 'ajax'=>true.
        if (! empty($form['ajax'])) {
            $this->addJs(base_url('themes/admin/default/assets/form-ajax.js'));
        }

        $this->assign('form', $form);
        // Il partial errors.tpl mostra il riepilogo errori in cima al form
        $this->assign('sessionErrors', session('errors'));
    }

    /**
     * Risposta unificata per il salvataggio di un form: JSON se la richiesta è
     * AJAX (form con 'ajax'=>true), altrimenti redirect + flash come di consueto.
     * Un solo code-path nel controller serve entrambe le modalità.
     *
     * @param bool  $ok   Esito del salvataggio
     * @param array $opts redirect, message, errors (per campo), withInput
     * @return \CodeIgniter\HTTP\ResponseInterface|\CodeIgniter\HTTP\RedirectResponse
     */
    protected function formResult(bool $ok, array $opts = [])
    {
        $redirect = $opts['redirect'] ?? null;
        $message  = $opts['message']  ?? ($ok ? 'Salvato con successo.' : 'Controlla i campi evidenziati.');
        $errors   = $opts['errors']   ?? [];

        if ($this->request->isAJAX()) {
            $payload = [
                'ok'      => $ok,
                'message' => $message,
                'csrf'    => csrf_hash(), // token ruotato, per la prossima submit
            ];
            if ($ok && $redirect !== null) {
                $payload['redirect'] = $redirect;
            }
            if (! $ok) {
                $payload['errors'] = $errors;
            }

            return $this->response
                ->setStatusCode($ok ? 200 : 422)
                ->setJSON($payload);
        }

        if ($ok) {
            return redirect()->to($redirect ?? current_url())->with('success', $message);
        }

        $r = redirect()->back()->withInput()->with('error', $message);

        return $errors !== [] ? $r->with('errors', $errors) : $r;
    }

    /**
     * Validazione server-side della logica condizionale del form: applica il
     * "required condizionale" dichiarato nelle `affects` in base ai valori
     * inviati. Rispecchia lato server ciò che form-logic.js fa lato client
     * (single source of truth: la stessa config del form).
     *
     * NB: è l'enforcement reale — il client è aggirabile. La validazione statica
     * del model resta a valle; questa copre i required che dipendono da altri campi.
     *
     * @param array      $config Config del form (con le affects)
     * @param array|null $post   Dati inviati (default: request POST)
     * @return array<string,string> Errori per campo (vuoto = ok)
     */
    protected function validateConditional(array $config, ?array $post = null): array
    {
        $post ??= $this->request->getPost() ?? [];
        $labels = [];
        $errors = [];

        foreach ($config['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $name => $cfg) {
                $labels[$name] = $cfg['label'] ?? $name;
            }
        }

        foreach ($config['sections'] ?? [] as $section) {
            foreach ($section['fields'] ?? [] as $name => $cfg) {
                foreach ($cfg['affects'] ?? [] as $affect) {
                    if (empty($affect['require']) || ! $this->affectActive($affect, $post[$name] ?? null)) {
                        continue;
                    }
                    foreach ((array) $affect['require'] as $target) {
                        $val = $post[$target] ?? null;
                        if ($val === null || $val === '' || $val === []) {
                            $errors[$target] = ($labels[$target] ?? $target) . ' è obbligatorio.';
                        }
                    }
                }
            }
        }

        return $errors;
    }

    /**
     * Indica se la condizione di una regola `affects` è attiva dato il valore
     * del campo sorgente. Rispecchia conditionActive() di form-logic.js.
     *
     * @param mixed $sourceValue
     */
    private function affectActive(array $affect, $sourceValue): bool
    {
        if (isset($affect['whenIn'])) {
            return in_array((string) $sourceValue, array_map('strval', (array) $affect['whenIn']), true);
        }
        if (! array_key_exists('when', $affect) || $affect['when'] === null) {
            return true;
        }
        if ($affect['when'] === true) {
            return $sourceValue !== null && $sourceValue !== '' && $sourceValue !== '0';
        }

        return (string) $sourceValue === (string) $affect['when'];
    }

    /**
     * Registra asset e init dei widget dei campi.
     *
     * Gli asset (CSS/JS della libreria) vengono aggiunti tramite addCss/addJs
     * che deduplicano: caricati una sola volta anche con più campi dello stesso
     * widget. L'init è generato per singolo campo (id-scoped) e raccolto in un
     * unico blocco inline eseguito dopo il caricamento delle librerie.
     *
     * @param array $form Struttura normalizzata dal FormBuilder
     * @return void
     */
    private function registerWidgets(array $form): void
    {
        $inits = [];

        foreach ($form['sections'] as $section) {
            foreach ($section['fields'] as $field) {
                if (empty($field['widget'])) {
                    continue;
                }

                $def = FieldWidgets::definition($field['widget']);
                if ($def === null) {
                    continue;
                }

                foreach ($def['css'] as $css) {
                    $this->addCss($css);
                }
                foreach ($def['js'] as $js) {
                    $this->addJs($js);
                }
                if (isset($def['init'])) {
                    $inits[] = ($def['init'])($field);
                }
            }
        }

        if ($inits !== []) {
            $this->addInlineJs(
                "document.addEventListener('DOMContentLoaded', function () {\n"
                . implode("\n", $inits)
                . "\n});"
            );
        }
    }
}
