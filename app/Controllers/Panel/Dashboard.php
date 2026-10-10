<?php

namespace App\Controllers\Panel;

use App\Controllers\BaseController;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Panel home. Temporary page until the dashboard of FASE-08 (docs/15 §2.2, L01-08, L08-01).
 */
class Dashboard extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-LAP-01'])]
    public function index(): string
    {
        return view('panel/dashboard/index', ['title' => 'Dashboard hari ini']);
    }
}
