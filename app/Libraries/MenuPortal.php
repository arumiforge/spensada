<?php

namespace App\Libraries;

use Config\Menu;

/**
 * Portal menu items the logged-in student may open (docs/08 UI-35, docs/09 §4.2, RT-21).
 */
class MenuPortal
{
    /**
     * Visible items in config order, each with `aktif` for aria-current.
     *
     * @return list<array<string, mixed>>
     */
    public function untuk(AkunAktif $akun, string $path): array
    {
        $routes = service('routes')->loadRoutes()->getRoutes('GET');
        $path   = trim($path, '/');
        $items  = [];

        foreach ((new Menu())->portal as $item) {
            // Pages of later phases have no route yet.
            if (array_filter($item['hak'], $akun->has(...)) === [] || ! isset($routes[$item['alamat']])) {
                continue;
            }

            $item['aktif'] = $path === $item['alamat'] || ($item['alamat'] !== 'portal' && str_starts_with($path, $item['alamat'] . '/'));
            $items[]       = $item;
        }

        return $items;
    }
}
