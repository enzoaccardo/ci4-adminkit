# ci4-adminkit

Kit per pannelli di amministrazione **CodeIgniter 4**, estratto dallo starter e riutilizzabile via Composer (come `codeigniter4/tasks`). Fornisce:

- **Smarty** come template engine (`AdminKit\Libraries\SmartyRenderer`, servizio `smarty`)
- **List builder**: trait `HasPagination` / `HasFilters` / `HasSorting` + partial `thead` / `filter_input` / `pagination` (liste con filtri, ordinamento, paginazione e whitelist colonne anti-SQLi)
- **Form builder** dichiarativo: `FormBuilder` + `FieldWidgets` + trait `HasForm` + partial `form.tpl` / `fields/*`
  - tipi campo per famiglia, inferenza `required`/`maxlength` dai `validationRules` del model
  - widget JS: **Tom Select**, **flatpickr**, **FilePond**, cron-builder, password-strength
  - **interazioni tra campi** (show/hide, enable, required condizionale, cascata AJAX)
  - submit **AJAX** opt-in (`formResult()`), validazione condizionale server (`validateConditional()`)
  - **option provider**: cascate AJAX senza boilerplate (rotta auto `admin/<slug>/options/<provider>`)
- **BaseAdminController**: rendering Smarty (precedenza tema-app → partial del kit), iniezione asset deduplicata, dispatcher option provider

Restano all'app: layout/tema + branding, autenticazione/RBAC, menu, migrazioni.

## Installazione (dev, path repository)
```jsonc
// composer.json dell'app
"repositories": [ { "type": "path", "url": "../ci4-adminkit" } ]
```
```bash
composer require enzoaccardo/ci4-adminkit:@dev
php spark adminkit:publish        # copia gli asset JS/CSS in public/ (--config per la config)
```

## Uso
Il controller base dell'app estende `AdminKit\Controllers\BaseAdminController` e aggiunge auth/menu/RBAC (via hook `prepareView()`). Poi list builder e form builder sono disponibili nei controller. Vedi lo starter `customci4` come consumer di riferimento.

## Convenzioni richieste all'app
- `window.__CSRF__` è iniettato dal kit; l'app può aggiungere `window.__APP__`.
- Un RBAC opzionale: sovrascrivere `authorize()` / `optionsPermission()` nel controller base dell'app.
- Config pubblicabile `AdminKit` (asset base, dir tema).
