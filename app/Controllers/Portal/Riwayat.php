<?php

namespace App\Controllers\Portal;

use App\Controllers\BaseController;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Portal home. Temporary page until the attendance history of FASE-08 (docs/15 §2.2, L02-09, L08-07).
 */
class Riwayat extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-LAP-04'])]
    public function index(): string
    {
        return view('portal/riwayat/index', ['title' => 'Riwayat']);
    }
}
