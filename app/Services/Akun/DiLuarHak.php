<?php

namespace App\Services\Akun;

use CodeIgniter\Exceptions\HTTPExceptionInterface;
use CodeIgniter\Exceptions\RuntimeException;

/**
 * Request outside the account's rights or scope (docs/09 RT-19 item 3).
 * The exception handler turns it into the 403 page.
 */
final class DiLuarHak extends RuntimeException implements HTTPExceptionInterface
{
    protected $code = 403;
}
