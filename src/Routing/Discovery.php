<?php

namespace AdminKit\Routing;

use AdminKit\Controllers\BaseAdminController;
use CodeIgniter\Router\RouteCollection;

/**
 * Auto-discovery delle rotte dichiarate nei controller.
 *
 * Convenzione: ogni controller espone un metodo statico
 *   public static function routes(RouteCollection $routes): void
 * (o publicRoutes()) in cui registra le proprie rotte. Questo helper scopre i
 * controller di un namespace ed invoca quel metodo — così l'app non ripete il
 * loop glob+class_exists in Config/Routes.php.
 *
 * Uso tipico (Config/Routes.php):
 *
 *   use AdminKit\Routing\Discovery;
 *
 *   Discovery::adminGroup($routes, 'App\Controllers\Admin');           // admin/* + auth + option-provider
 *   Discovery::discover($routes, 'App\Controllers\Front\Auth');        // rotte pubbliche auth
 *   Discovery::discover($routes, 'App\Controllers\Api', 'publicRoutes');
 *   $routes->group('api/v1', ['filter' => 'jwt'], static function ($routes) {
 *       Discovery::discover($routes, 'App\Controllers\Api');
 *   });
 */
class Discovery
{
    /**
     * Scopre i controller (*.php, non ricorsivo) di $namespace ed invoca il loro
     * metodo statico $method, se presente. Con $withOptions registra anche la
     * rotta option-provider del form builder per i controller che espongono
     * formOptions() → GET <slug>/options/(:segment).
     *
     * @param string $namespace   FQN dei controller (es. 'App\Controllers\Admin')
     * @param string $method      'routes' | 'publicRoutes'
     * @param bool   $withOptions registra le rotte option-provider (pannello)
     */
    public static function discover(
        RouteCollection $routes,
        string $namespace,
        string $method = 'routes',
        bool $withOptions = false
    ): void {
        $namespace = trim($namespace, '\\');
        $dir       = static::namespaceToPath($namespace);
        if ($dir === null) {
            return;
        }

        foreach (glob($dir . '/*.php') as $file) {
            $class = $namespace . '\\' . basename($file, '.php');

            if (! class_exists($class) || ! method_exists($class, $method)) {
                continue;
            }

            $class::$method($routes);

            if ($withOptions) {
                $slug = BaseAdminController::controllerSlug($class);
                if (method_exists($class, 'formOptions')) {
                    $routes->get("{$slug}/options/(:segment)", [$class, 'formOptions']);
                }
                // "Crea nuovo" in modale: GET (fragment) + POST (store).
                if (method_exists($class, 'formCreate')) {
                    $routes->match(['get', 'post'], "{$slug}/create/(:segment)", [$class, 'formCreate']);
                }
            }
        }
    }

    /**
     * Scorciatoia per il pannello: crea il gruppo (default `admin` con filtro
     * `auth`) e scopre i controller invocando routes() + le rotte option-provider.
     *
     * @param array<string,mixed> $groupOptions opzioni del gruppo (filtro, namespace, ...)
     */
    public static function adminGroup(
        RouteCollection $routes,
        string $namespace,
        string $prefix = 'admin',
        array $groupOptions = ['filter' => 'auth']
    ): void {
        $routes->group($prefix, $groupOptions, static function ($routes) use ($namespace) {
            static::discover($routes, $namespace, 'routes', true);
        });
    }

    /**
     * Risolve un namespace di controller nella cartella su disco.
     *
     * Prima prova le mappe PSR-4 registrate nell'autoloader (gestisce qualsiasi
     * namespace, anche di moduli/composer); poi, come fallback robusto in ogni
     * contesto (incluso PHPUnit, dove l'autoloader può non esporre ancora le
     * mappe), risolve il prefisso `App\` su APPPATH — come faceva il loop
     * originale in Config/Routes.php.
     */
    private static function namespaceToPath(string $namespace): ?string
    {
        $bestPrefix = null;
        $bestPaths  = [];

        foreach (service('autoloader')->getNamespace() as $prefix => $paths) {
            $prefix = trim($prefix, '\\');
            if ($namespace === $prefix || str_starts_with($namespace, $prefix . '\\')) {
                if ($bestPrefix === null || strlen($prefix) > strlen($bestPrefix)) {
                    $bestPrefix = $prefix;
                    $bestPaths  = $paths;
                }
            }
        }

        if ($bestPrefix !== null) {
            $subPath = str_replace('\\', '/', trim(substr($namespace, strlen($bestPrefix)), '\\'));
            foreach ($bestPaths as $base) {
                $dir = rtrim($base, '/\\') . ($subPath !== '' ? '/' . $subPath : '');
                if (is_dir($dir)) {
                    return $dir;
                }
            }
        }

        // Fallback: App\... → APPPATH/... (sempre valido, anche nei test)
        if (str_starts_with($namespace, 'App\\')) {
            $subPath = str_replace('\\', '/', substr($namespace, 4));
            $dir     = rtrim(APPPATH, '/\\') . '/' . $subPath;
            if (is_dir($dir)) {
                return $dir;
            }
        }

        return null;
    }
}
