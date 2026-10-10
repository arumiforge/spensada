<?php

namespace App\Controllers;

use App\Libraries\AppExceptionHandler;
use CodeIgniter\HTTP\ResponseInterface;

/**
 * 404 page through Config\Routing::$override404 (docs/09 RT-19 item 2).
 */
class Galat extends BaseController
{
    public function tidakDitemukan(): ResponseInterface
    {
        return AppExceptionHandler::prepare(404, $this->request, $this->response);
    }
}
