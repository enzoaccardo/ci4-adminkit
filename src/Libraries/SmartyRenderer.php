<?php

namespace AdminKit\Libraries;

use CodeIgniter\View\RendererInterface;
use Smarty\Smarty;

class SmartyRenderer implements RendererInterface
{
    protected Smarty $smarty;
    protected array  $data = [];

    /** Log dei render per il collector della debug toolbar (solo in development). */
    protected bool  $debug    = false;
    protected array $debugLog = [];

    public function __construct()
    {
        $this->smarty = new Smarty();

        $this->smarty->setTemplateDir(APPPATH . 'Views/');
        $this->smarty->setCompileDir(WRITEPATH . 'cache/smarty/compile/');
        $this->smarty->setCacheDir(WRITEPATH . 'cache/smarty/cache/');
        $this->smarty->setConfigDir(APPPATH . 'Views/smarty_config/');

        $this->smarty->caching     = false;
        $this->smarty->escape_html = true;
        $this->debug               = (ENVIRONMENT === 'development');
        $this->smarty->debugging   = false; // il debug Smarty è esposto via il collector CI4

        $this->registerCIHelpers();
    }

    private function registerCIHelpers(): void
    {
        helper(['url', 'form', 'security']);

        $this->smarty->registerPlugin('function', 'base_url', static function (array $params): string {
            return base_url($params['path'] ?? '');
        });

        $this->smarty->registerPlugin('function', 'site_url', static function (array $params): string {
            return site_url($params['path'] ?? '');
        });

        $this->smarty->registerPlugin('function', 'current_url', static function (): string {
            return current_url();
        });

        $this->smarty->registerPlugin('function', 'csrf_field', static function (): string {
            return csrf_field();
        });

        $this->smarty->registerPlugin('function', 'paginate_url', static function (array $params): string {
            $get         = service('request')->getGet() ?? [];
            $get['page'] = (int) ($params['page'] ?? 1);
            return current_url() . '?' . http_build_query($get);
        });

        $this->smarty->registerPlugin('function', 'sort_url', static function (array $params): string {
            $get         = service('request')->getGet() ?? [];
            $get['sort'] = $params['sort'] ?? '';
            $get['dir']  = $params['dir'] ?? 'asc';
            unset($get['page']);
            return current_url() . '?' . http_build_query($get);
        });

        $this->smarty->registerPlugin('modifier', 'str_contains', static function (string $haystack, string $needle): bool {
            return str_contains($haystack, $needle);
        });

        $this->smarty->registerPlugin('function', 'nav_menu', static function (array $params): string {
            return SmartyRenderer::renderNavTree($params['items'] ?? [], $params['current_path'] ?? '');
        });

        $this->smarty->registerPlugin('function', 'old', static function (array $params): string {
            $value = old($params['field'] ?? '', $params['default'] ?? null);
            return $value !== null ? htmlspecialchars((string) $value, ENT_QUOTES) : '';
        });
    }

    public function render(string $view, ?array $options = null, bool $saveData = false): string
    {
        $this->smarty->assign($this->data);

        $start  = $this->debug ? microtime(true) : 0.0;
        $output = $this->smarty->fetch($view . '.tpl');
        $this->logRender($view . '.tpl', $start);

        if (! $saveData) {
            $this->resetData();
        }

        return $output;
    }

    public function renderString(string $view, ?array $options = null, bool $saveData = false): string
    {
        $this->smarty->assign($this->data);

        $start  = $this->debug ? microtime(true) : 0.0;
        $output = $this->smarty->fetch('string:' . $view);
        $this->logRender('string:…', $start);

        if (! $saveData) {
            $this->resetData();
        }

        return $output;
    }

    /**
     * Registra un render per il collector della debug toolbar (solo in dev).
     */
    private function logRender(string $file, float $start): void
    {
        if (! $this->debug) {
            return;
        }

        $end = microtime(true);
        $this->debugLog[] = [
            'template' => $file,
            'path'     => $this->resolveTemplatePath($file),
            'start'    => $start,
            'duration' => $end - $start,
            'vars'     => array_keys($this->data),
        ];
    }

    /**
     * Risolve il .tpl nella catena templateDir (primo esistente = quello usato),
     * relativizzato a ROOTPATH per leggibilità nella toolbar.
     */
    private function resolveTemplatePath(string $file): string
    {
        if (str_starts_with($file, 'string:')) {
            return '(string)';
        }

        foreach ((array) $this->smarty->getTemplateDir() as $dir) {
            $full = rtrim((string) $dir, '/\\') . '/' . $file;
            if (is_file($full)) {
                return defined('ROOTPATH') && str_starts_with($full, ROOTPATH)
                    ? substr($full, strlen(ROOTPATH))
                    : $full;
            }
        }

        return $file . ' (non risolto)';
    }

    /**
     * Log dei render della richiesta corrente (per AdminKit\Debug\SmartyCollector).
     *
     * @return list<array{template:string,path:string,start:float,duration:float,vars:list<string>}>
     */
    public function getDebugLog(): array
    {
        return $this->debugLog;
    }

    public function setData(array $data = [], ?string $context = null): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function setVar(string $name, $value = null, ?string $context = null): static
    {
        $this->data[$name] = $value;
        return $this;
    }

    public function resetData(): static
    {
        $this->data = [];
        $this->smarty->clearAllAssign();
        return $this;
    }

    public function assign(string $key, mixed $value): static
    {
        $this->data[$key] = $value;
        return $this;
    }

    public static function renderNavTree(array $items, string $currentPath): string
    {
        $html = '';
        foreach ($items as $item) {
            $html .= self::renderNavMenuItem($item, $currentPath);
        }
        return $html;
    }

    private static function renderNavMenuItem(object $item, string $currentPath): string
    {
        $label = htmlspecialchars($item->label,       ENT_QUOTES | ENT_HTML5, 'UTF-8');
        $icon  = htmlspecialchars($item->icon ?? 'bi-circle', ENT_QUOTES | ENT_HTML5, 'UTF-8');

        if (! empty($item->children)) {
            $isOpen = self::isNavDescendantActive($item->children, $currentPath);
            $href   = $item->path ? base_url($item->path) : '#';
            $html   = '<li class="nav-item' . ($isOpen ? ' menu-open' : '') . '">';
            $html  .= '<a href="' . $href . '" class="nav-link' . ($isOpen ? ' active' : '') . '">';
            $html  .= '<i class="nav-icon bi ' . $icon . '"></i>';
            $html  .= '<p>' . $label . ' <i class="nav-arrow bi bi-chevron-right"></i></p>';
            $html  .= '</a>';
            $html  .= '<ul class="nav nav-treeview ps-3">';
            $html  .= self::renderNavTree($item->children, $currentPath);
            $html  .= '</ul></li>';
        } else {
            $href           = $item->path ? base_url($item->path) : '#';
            $normalizedPath = $item->path ? ('/' . $item->path) : null;
            $isActive       = $normalizedPath !== null && (
                $currentPath === $normalizedPath ||
                str_starts_with($currentPath, $normalizedPath . '/')
            );
            $html  = '<li class="nav-item">';
            $html .= '<a href="' . $href . '" class="nav-link' . ($isActive ? ' active' : '') . '">';
            $html .= '<i class="nav-icon bi ' . $icon . '"></i>';
            $html .= '<p>' . $label . '</p>';
            $html .= '</a></li>';
        }

        return $html;
    }

    private static function isNavDescendantActive(array $items, string $currentPath): bool
    {
        foreach ($items as $item) {
            if ($item->path) {
                $normalizedPath = '/' . $item->path;
                if ($currentPath === $normalizedPath || str_starts_with($currentPath, $normalizedPath . '/')) {
                    return true;
                }
            }
            if (! empty($item->children) && self::isNavDescendantActive($item->children, $currentPath)) {
                return true;
            }
        }
        return false;
    }

    public function setTemplateDir(string|array $dir): static
    {
        $this->smarty->setTemplateDir($dir);
        return $this;
    }

    public function getSmarty(): Smarty
    {
        return $this->smarty;
    }

    public function clearCompiledTemplates(?string $env = null): int
    {
        return $this->smarty->clearCompiledTemplate(null, $env);
    }

    public function countCompiledTemplates(?string $env = null): int
    {
        $dir = rtrim($this->smarty->getCompileDir(), '/\\') . '/';

        if ($env === null) {
            return count(glob($dir . '*.php') ?: []);
        }

        return count(glob($dir . '*^' . $env . '^*.php') ?: []);
    }
}