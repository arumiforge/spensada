<?php

namespace App\Database\Seeds;

use CodeIgniter\Database\Seeder;
use RuntimeException;

/**
 * Fictional data for local work and tests (docs/07 ARS-18). Later steps add
 * their data here (docs/15 §3 rule 3). Never runs in production.
 */
class DataContoh extends Seeder
{
    public function run(): void
    {
        // CI4 boots as production when CI_ENVIRONMENT is unset, hence the fallback.
        if (env('CI_ENVIRONMENT', ENVIRONMENT) === 'production') {
            throw new RuntimeException('Data contoh tidak boleh diisi di server production. Perintah dihentikan, tidak ada data yang berubah.');
        }
    }
}
