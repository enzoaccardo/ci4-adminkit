# ci4-adminkit

Base per costruire pannelli di amministrazione con CodeIgniter 4.

È nato mettendo insieme le cose che riscrivevo ogni volta in un gestionale: il
rendering delle viste, le liste con filtri e ordinamento, i form, la gestione dei
permessi. Qui dentro c'è la parte che non dipende dal dominio dell'applicazione,
così su un progetto nuovo parti da qualcosa invece che dal foglio bianco.

## In due parole

Il pezzo centrale è `BaseAdminController`. Un controller che lo estende ha già:

* le viste renderizzate con Smarty 5, con gli helper di CI4 già registrati
  (`base_url`, `csrf_field`, `sort_url`, il menu, ecc.);
* un *list builder* fatto di tre trait (paginazione, filtri, ordinamento) che
  lavorano su una whitelist di colonne, quindi niente parametri che finiscono
  dritti nella query;
* un *form builder* dichiarativo: descrivi i campi in un array e vengono fuori
  validazione lato client, widget (Tom Select, flatpickr, FilePond, cron), le
  interazioni tra campi (mostra/nascondi, obbligatori condizionati, cascate via
  AJAX), il submit in AJAX e la creazione al volo in una modale.

Ci sono poi un `BaseModel` e una `BaseMigration` con i campi di audit
(`created_by` / `updated_by` / `deleted_by` e soft delete), un helper per
registrare le rotte partendo dai controller (`Routing\Discovery`) e un contratto
RBAC opzionale: se in giro c'è un `service('rbac')` che lo implementa, il
controllo dei permessi si attiva da solo, altrimenti il kit resta chiuso di
default sulle azioni protette.

Quello che il kit *non* fa, di proposito: non impone un tema (il markup usa le
classi di Bootstrap ma il CSS lo porti tu), non decide come autentichi gli
utenti, non crea il menu. Per il tema pronto c'è `ci4-adminkit-adminlte4`.

## Installazione

```
composer require enzoaccardo/ci4-adminkit
php spark adminkit:publish
```

Il `publish` copia gli asset dei widget in `public/`. Richiede PHP 8.2+,
CodeIgniter 4.7 e Smarty 5 (tirato dentro come dipendenza).

## Un form, tanto per capirsi

```php
protected function formConfig(): array
{
    return [
        'action'   => base_url('admin/utenti/salva'),
        'sections' => [[
            'title'  => 'Anagrafica',
            'fields' => [
                'nome'  => ['type' => 'text',  'label' => 'Nome', 'required' => true],
                'email' => ['type' => 'email', 'label' => 'Email'],
                'ruolo' => ['type' => 'select', 'label' => 'Ruolo', 'widget' => 'tomselect',
                            'options' => $this->ruoli()],
            ],
        ]],
    ];
}
```

Nel controller poi `buildForm($this->formConfig())` e nella view
`{include file='layout/_partials/form.tpl'}`. Il resto lo fa il builder.

## Licenza

MIT. Vedi il file [LICENSE](LICENSE).
