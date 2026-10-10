<?php

namespace Tests\Support\Controllers;

use App\Controllers\BaseController;
use CodeIgniter\Router\Attributes\Filter;

/**
 * Fixture for HakAttributeTest: one guarded and one unguarded method.
 */
class HakFixture extends BaseController
{
    #[Filter(by: 'hak', having: ['HA-AKN-02'])]
    public function guarded(): string
    {
        return 'ok';
    }

    public function unguarded(): string
    {
        return 'ok';
    }

    #[Filter(by: 'hak', having: ['HA-TIDAK-ADA'])]
    public function unknownRight(): string
    {
        return 'ok';
    }
}
