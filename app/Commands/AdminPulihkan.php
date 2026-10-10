<?php

namespace App\Commands;

use App\Services\Akun\AdminAwal;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * `php spark admin:pulihkan <username>` (docs/07 ARS-49 item 2), for when
 * the only admin forgot the password (docs/03 UF-21 E1).
 */
class AdminPulihkan extends BaseCommand
{
    protected $group       = 'Spensada';
    protected $name        = 'admin:pulihkan';
    protected $description = 'Membuat password baru untuk akun admin yang aktif.';
    protected $usage       = 'admin:pulihkan <username>';
    protected $arguments   = ['username' => 'Username akun admin.'];

    public function run(array $params): int
    {
        $username = $params[0] ?? CLI::prompt('Username admin', null, 'required');

        $hasil = (new AdminAwal())->pulihkan((string) $username);

        if (isset($hasil['galat'])) {
            CLI::error($hasil['galat']);

            return EXIT_ERROR;
        }

        CLI::write('Password admin sudah diganti. Semua sesi login akun ini berakhir.', 'green');
        CLI::write('Password: ' . CLI::color($hasil['password'], 'yellow'));
        CLI::write('Password ini hanya tampil sekali. Catat sekarang. Admin wajib menggantinya saat login berikutnya.');

        return EXIT_SUCCESS;
    }
}
