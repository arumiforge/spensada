<?php

namespace App\Controllers\Publik;

use App\Controllers\BaseController;
use App\Services\Berkas\Penyaji;
use App\Services\MasterData\IdentitasSekolah;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * School logo without login (docs/09 HAL-MD-01, §13; docs/04 §4.9 item 2).
 */
class Logo extends BaseController
{
    public function index(): ResponseInterface
    {
        return (new Penyaji())->logo((new IdentitasSekolah())->logo());
    }
}
