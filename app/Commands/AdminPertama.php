<?php

namespace App\Commands;

use App\Services\Akun\AdminAwal;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark admin:pertama` (docs/07 ARS-49 item 1).
 */
class AdminPertama extends BaseCommand
{
    protected $group       = 'Spensada';
    protected $name        = 'admin:pertama';
    protected $description = 'Membuat akun admin pertama.';
    protected $usage       = 'admin:pertama [--nama "Nama Admin"] [--username admin]';
    protected $options     = [
        '--nama'     => 'Nama admin. Ditanyakan bila tidak diisi.',
        '--username' => 'Username admin. Ditanyakan bila tidak diisi.',
    ];

    public function run(array $params): int
    {
        // spark passes --options in $params too.
        $nama     = $params['nama'] ?? CLI::prompt('Nama admin', null, 'required');
        $username = $params['username'] ?? CLI::prompt('Username', null, 'required');

        $hasil = (new AdminAwal())->buatPertama((string) $nama, (string) $username);

        if (isset($hasil['galat'])) {
            CLI::error($hasil['galat']);

            return EXIT_ERROR;
        }

        CLI::write('Akun admin sudah dibuat.', 'green');
        CLI::write('Password: ' . CLI::color($hasil['password'], 'yellow'));
        CLI::write('Password ini hanya tampil sekali. Catat sekarang. Admin wajib menggantinya saat login pertama.');

        return EXIT_SUCCESS;
    }
}
