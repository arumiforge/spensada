<?php

namespace Config;

use CodeIgniter\Config\BaseConfig;

/**
 * Technical parameters that admins do not change (docs/07 ARS-17).
 * Every value can be overridden in .env as `spensada.<property>`.
 */
class Spensada extends BaseConfig
{
    /**
     * App version, appended to asset URLs as `?v=` (docs/08 UI-73).
     */
    public string $versi = '0.1.0';
}
