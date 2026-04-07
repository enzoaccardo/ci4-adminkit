<?php

namespace AdminKit\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Pubblica gli asset front-end del kit (JS/CSS + vendor) nella cartella public
 * dell'app, e opzionalmente la config. Da eseguire dopo l'installazione e ad
 * ogni aggiornamento del pacchetto.
 *
 *   php spark adminkit:publish
 */
class AdminKitPublish extends BaseCommand
{
    protected $group       = 'AdminKit';
    protected $name        = 'adminkit:publish';
    protected $description = 'Pubblica gli asset del kit admin in public/ (e la config con --config).';
    protected $usage       = 'adminkit:publish [--config]';
    protected $options     = ['--config' => 'Pubblica anche app/Config/AdminKit.php'];

    public function run(array $params): int
    {
        $cfg    = config('AdminKit');
        $source = realpath(__DIR__ . '/../../assets');
        $dest   = rtrim(FCPATH, '/\\') . '/' . trim($cfg->assetBase, '/');

        if ($source === false) {
            CLI::error('Cartella assets del kit non trovata.');
            return EXIT_ERROR;
        }

        $count = $this->copyRecursive($source, $dest);
        CLI::write(CLI::color("Pubblicati {$count} asset in ", 'green') . $dest);

        if (array_key_exists('config', $params) || CLI::getOption('config')) {
            $this->publishConfig();
        }

        CLI::write('Fatto.', 'green');
        return EXIT_SUCCESS;
    }

    private function copyRecursive(string $src, string $dst): int
    {
        if (! is_dir($dst)) {
            mkdir($dst, 0777, true);
        }

        $count = 0;
        foreach (scandir($src) as $entry) {
            if ($entry === '.' || $entry === '..') {
                continue;
            }
            $from = $src . '/' . $entry;
            $to   = $dst . '/' . $entry;
            if (is_dir($from)) {
                $count += $this->copyRecursive($from, $to);
            } else {
                copy($from, $to);
                $count++;
            }
        }

        return $count;
    }

    private function publishConfig(): void
    {
        $from = realpath(__DIR__ . '/../Config/AdminKit.php');
        $to   = APPPATH . 'Config/AdminKit.php';

        if ($from === false) {
            return;
        }

        $content = file_get_contents($from);
        // riporta la config nel namespace dell'app come override
        $content = str_replace('namespace AdminKit\\Config;', 'namespace Config;', $content);
        $content = str_replace(
            'class AdminKit extends BaseConfig',
            'class AdminKit extends \\AdminKit\\Config\\AdminKit',
            $content
        );

        if (is_file($to)) {
            CLI::write('app/Config/AdminKit.php già presente, salto.', 'yellow');
            return;
        }

        file_put_contents($to, $content);
        CLI::write('Config pubblicata: app/Config/AdminKit.php', 'green');
    }
}
