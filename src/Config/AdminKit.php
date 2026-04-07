<?php

namespace AdminKit\Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Configurazione del kit admin. Pubblicabile nell'app con `spark adminkit:publish`
 * (verrà copiata in app/Config/AdminKit.php e potrà sovrascrivere questi default).
 */
class AdminKit extends BaseConfig
{
    /**
     * Path pubblico (relativo alla baseURL) dove vengono pubblicati gli asset
     * del kit. Deve combaciare con la destinazione di `adminkit:publish`.
     */
    public string $assetBase = 'themes/admin/default/assets';

    /**
     * Directory del tema dell'app (relativa a APPPATH.Views) i cui template
     * hanno precedenza sui partial del kit. Permette override per-progetto.
     */
    public string $themeDir = 'themes/admin/default';
}
