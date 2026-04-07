<?php

namespace AdminKit\Config;

use AdminKit\Libraries\SmartyRenderer;
use CodeIgniter\Config\BaseService;

/**
 * Servizi del kit. Auto-scoperti da CodeIgniter. Se l'app definisce già
 * `smarty` in App\Config\Services, quella ha precedenza.
 */
class Services extends BaseService
{
    public static function smarty(bool $getShared = true): SmartyRenderer
    {
        if ($getShared) {
            return static::getSharedInstance('smarty');
        }

        return new SmartyRenderer();
    }
}
