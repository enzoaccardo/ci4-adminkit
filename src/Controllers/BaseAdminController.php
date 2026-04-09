<?php

namespace AdminKit\Controllers;

use AdminKit\Libraries\SmartyRenderer;
use AdminKit\Traits\HasFilters;
use AdminKit\Traits\HasForm;
use AdminKit\Traits\HasPagination;
use AdminKit\Traits\HasSorting;
use CodeIgniter\Controller;
use CodeIgniter\Exceptions\PageNotFoundException;
use CodeIgniter\HTTP\RequestInterface;
use CodeIgniter\HTTP\ResponseInterface;
use Psr\Log\LoggerInterface;
use ReflectionMethod;

/**
 * Controller base del kit admin. Fornisce: rendering Smarty (con precedenza
 * tema-app → partial del kit), iniezione asset deduplicata, list builder e form
 * builder (via trait), e il dispatcher degli option provider (cascate AJAX).
 *
 * L'app estende questa classe nel proprio AdminController aggiungendo auth,
 * menu, RBAC e branding tramite l'hook prepareView().
 */
abstract class BaseAdminController extends Controller
{
    use HasPagination;
    use HasFilters;
    use HasSorting;
    use HasForm;

    protected SmartyRenderer $smarty;

    private array $cssLinks      = [];
    private array $cssInline     = [];
    private array $headScripts   = [];
    private array $footerScripts = [];

    public function initController(RequestInterface $request, ResponseInterface $response, LoggerInterface $logger): void
    {
        parent::initController($request, $response, $logger);

        $this->smarty = service('smarty');
        $this->smarty->setTemplateDir($this->templateDirs());
        $this->smarty->getSmarty()->compile_id = 'admin';
    }

    /**
     * Catena di risoluzione dei template Smarty, in ordine di precedenza.
     * Default: tema dell'app (override) → viste del kit (partial di base).
     *
     * Estendibile: un pacchetto-tema (es. ci4-adminkit-adminlte4) sovrascrive
     * questo metodo per inserire le proprie viste di layout tra il tema dell'app
     * e i partial del kit — senza duplicare initController().
     *
     * @return list<string>
     */
    protected function templateDirs(): array
    {
        $cfg = config('AdminKit');

        return [
            APPPATH . 'Views/' . rtrim($cfg->themeDir, '/') . '/',
            __DIR__ . '/../Views/',
        ];
    }

    // -------------------------------------------------------------------------
    // Asset injection (deduplicati)
    // -------------------------------------------------------------------------

    protected function addCss(string $url): static
    {
        if (! in_array($url, $this->cssLinks, true)) {
            $this->cssLinks[] = $url;
        }
        return $this;
    }

    protected function addInlineCss(string $code): static
    {
        $this->cssInline[] = $code;
        return $this;
    }

    protected function addHeadJs(string $url): static
    {
        foreach ($this->headScripts as $item) {
            if ($item['type'] === 'url' && $item['content'] === $url) {
                return $this;
            }
        }
        $this->headScripts[] = ['type' => 'url', 'content' => $url];
        return $this;
    }

    protected function addHeadInlineJs(string $code): static
    {
        $this->headScripts[] = ['type' => 'inline', 'content' => $code];
        return $this;
    }

    protected function addJs(string $url): static
    {
        foreach ($this->footerScripts as $item) {
            if ($item['type'] === 'url' && $item['content'] === $url) {
                return $this;
            }
        }
        $this->footerScripts[] = ['type' => 'url', 'content' => $url];
        return $this;
    }

    protected function addInlineJs(string $code): static
    {
        $this->footerScripts[] = ['type' => 'inline', 'content' => $code];
        return $this;
    }

    // -------------------------------------------------------------------------
    // Rendering
    // -------------------------------------------------------------------------

    protected function assign(string $key, mixed $value): static
    {
        $this->smarty->assign($key, $value);
        return $this;
    }

    /**
     * Legge il body della request come JSON (con workaround Apache/PHP-FPM che
     * consegna il body URL-encoded). Utile per gli endpoint JSON del pannello.
     */
    protected function getJsonBody(): array
    {
        $raw = $this->request->getBody();

        if ($raw === null || $raw === '') {
            return [];
        }

        if (str_starts_with($raw, '%')) {
            $raw = rtrim(urldecode($raw), '=');
        }

        return json_decode($raw, true) ?? [];
    }

    /**
     * Hook per i dati condivisi del layout (menu, avatar, branding, ...).
     * L'app lo sovrascrive; qui è un no-op.
     */
    protected function prepareView(): void
    {
    }

    protected function render(string $view, bool $saveData = false): string
    {
        // dati specifici dell'app (menu/avatar/__APP__/...)
        $this->prepareView();

        $this->smarty->assign('flashSuccess', session('success'));
        $this->smarty->assign('flashError',   session('error'));
        $this->smarty->assign('flashWarning', session('warning'));
        $this->smarty->assign('currentPath', '/' . ltrim(uri_string(), '/'));

        // CSRF globale JS — primo script del footer
        $csrfScript = 'window.__CSRF__ = ' . json_encode([
            'name'  => csrf_token(),
            'value' => csrf_hash(),
        ]) . ';';

        $this->smarty->assign('cssLinks',    $this->cssLinks);
        $this->smarty->assign('cssInline',   $this->cssInline);
        $this->smarty->assign('headScripts', $this->headScripts);
        $this->smarty->assign('footerScripts', array_merge(
            [['type' => 'inline', 'content' => $csrfScript]],
            $this->footerScripts
        ));

        return $this->smarty->render($view, saveData: $saveData);
    }

    // -------------------------------------------------------------------------
    // Fragment del form per la modale "crea nuovo"
    // -------------------------------------------------------------------------

    /**
     * Renderizza SOLO il form (senza layout) e ne restituisce gli asset, come
     * JSON {html, css[], js[], init}, per l'iniezione in una modale (modal-form.js).
     * Riusa la stessa formConfig() del create dell'entità: nessuna duplicazione.
     *
     * L'azione del form (create) deve rispondere via formResult(true, ['record'=>...])
     * così la modale può iniettare il nuovo record nel select del form padre.
     */
    protected function formFragment(array $config, ?object $entity = null, ?\CodeIgniter\Model $model = null): ResponseInterface
    {
        $form = (new \AdminKit\Libraries\FormBuilder())->build($config, $entity, $model, $this->formOptionsBase());

        $this->assign('form', $form);
        $this->assign('sessionErrors', null);
        $this->assign('flashSuccess', null);
        $this->assign('flashError', null);
        $this->assign('flashWarning', null);

        $html   = $this->smarty->render('layout/_partials/form');
        $assets = $this->collectWidgetAssets($form);

        return $this->response->setJSON([
            'html' => $html,
            'css'  => $assets['css'],
            'js'   => $assets['js'],
            'init' => implode("\n", $assets['init']),
        ]);
    }

    // -------------------------------------------------------------------------
    // Option provider (cascate AJAX dei campi remoti)
    // -------------------------------------------------------------------------

    public static function controllerSlug(string $class): string
    {
        $base = substr($class, (int) strrpos($class, '\\') + 1);

        return strtolower((string) preg_replace('/(?<!^)[A-Z]/', '-$0', $base));
    }

    protected function formOptionsBase(): string
    {
        return base_url('admin/' . static::controllerSlug(static::class) . '/options');
    }

    /**
     * Dispatcher generico degli option provider. Rotta auto-registrata:
     * GET admin/<slug>/options/(:segment). Chiama options{Provider}() (protected).
     */
    public function formOptions(string $provider): ResponseInterface
    {
        if (! preg_match('/^[a-z0-9-]+$/', $provider)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $method = 'options' . str_replace(' ', '', ucwords(str_replace('-', ' ', $provider)));

        if (! method_exists($this, $method) || ! (new ReflectionMethod($this, $method))->isProtected()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $permission = $this->optionsPermission($provider);
        if ($permission !== null) {
            $this->authorize($permission);
        }

        return $this->response->setJSON($this->{$method}());
    }

    /**
     * Permesso RBAC per un provider (null = solo auth). Da sovrascrivere.
     */
    protected function optionsPermission(string $provider): ?string
    {
        return null;
    }

    // -------------------------------------------------------------------------
    // "Crea nuovo" in modale — auto-rotta (mirror dell'option provider)
    // -------------------------------------------------------------------------

    protected function formCreateBase(): string
    {
        return base_url('admin/' . static::controllerSlug(static::class) . '/create');
    }

    /**
     * Dispatcher del "crea nuovo" in modale. Rotta auto-registrata:
     * (GET|POST) admin/<slug>/create/(:segment).
     *   GET  → renderizza il fragment del form di createConfig{Provider}() (protected);
     *   POST → salva via createStore{Provider}() (protected), che risponde con
     *          formResult(true, ['record'=>['value','label']]).
     */
    public function formCreate(string $provider): ResponseInterface
    {
        if (! preg_match('/^[a-z0-9-]+$/', $provider)) {
            throw PageNotFoundException::forPageNotFound();
        }

        $studly       = str_replace(' ', '', ucwords(str_replace('-', ' ', $provider)));
        $configMethod = 'createConfig' . $studly;
        $storeMethod  = 'createStore' . $studly;

        if (! method_exists($this, $configMethod) || ! (new ReflectionMethod($this, $configMethod))->isProtected()) {
            throw PageNotFoundException::forPageNotFound();
        }

        $permission = $this->createPermission($provider);
        if ($permission !== null) {
            $this->authorize($permission);
        }

        if (strtoupper($this->request->getMethod()) === 'POST') {
            if (! method_exists($this, $storeMethod) || ! (new ReflectionMethod($this, $storeMethod))->isProtected()) {
                throw PageNotFoundException::forPageNotFound();
            }

            return $this->{$storeMethod}();
        }

        // GET → fragment del form (stessa formConfig del create dell'entità)
        $config           = $this->{$configMethod}();
        $config['ajax']   = true;
        $config['action'] = $this->formCreateBase() . '/' . $provider;

        return $this->formFragment($config);
    }

    /**
     * Permesso RBAC per il "crea nuovo" di un provider (null = solo auth).
     */
    protected function createPermission(string $provider): ?string
    {
        return null;
    }

    /**
     * Servizio RBAC scoperto a runtime (soft-discovery): se un pacchetto/app
     * registra service('rbac') che implementa AdminKit\Contracts\Rbac, viene
     * usato; altrimenti null (nessun RBAC installato).
     */
    private function rbac(): ?\AdminKit\Contracts\Rbac
    {
        try {
            $svc = service('rbac');
        } catch (\Throwable) {
            return null;
        }

        return $svc instanceof \AdminKit\Contracts\Rbac ? $svc : null;
    }

    /**
     * Vero se l'utente ha il permesso. Delega al service('rbac') scoperto; se
     * nessun RBAC è installato ritorna false (fail-closed). Usata dagli endpoint
     * JSON che vogliono decidere senza lanciare eccezioni.
     */
    protected function can(string $permission): bool
    {
        $rbac = $this->rbac();

        return $rbac !== null && ($rbac->isSuperAdmin() || $rbac->can($permission));
    }

    /**
     * Verifica RBAC. Delega al service('rbac') scoperto (soft-discovery). Se
     * nessun RBAC è installato → FAIL-CLOSED: eccezione esplicita (mai un bypass
     * silenzioso). Un'app con RBAC proprio può comunque sovrascrivere authorize()
     * (es. via trait) e la sua versione ha precedenza.
     *
     * @throws \RuntimeException se un permesso è richiesto senza RBAC installato
     */
    protected function authorize(string $permission): void
    {
        $rbac = $this->rbac();

        if ($rbac === null) {
            throw new \RuntimeException(sprintf(
                'Permesso "%s" richiesto ma nessun RBAC è installato (service(\'rbac\') '
                . 'assente o non implementa AdminKit\\Contracts\\Rbac).',
                $permission
            ));
        }

        $rbac->authorize($permission);
    }
}
