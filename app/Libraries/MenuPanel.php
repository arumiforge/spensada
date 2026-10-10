<?php

namespace App\Libraries;

use Config\Menu;

/**
 * Panel menu items the logged-in account may open (docs/08 UI-31, docs/09 §4.1, RT-21).
 */
class MenuPanel
{
    /**
     * Visible items grouped by menu group, in config order; the null group
     * (top items) has key ''. Each item gets `aktif` for aria-current.
     *
     * @return array<string, list<array<string, mixed>>>
     */
    public function untuk(AkunAktif $akun, string $path): array
    {
        $routes = service('routes')->loadRoutes()->getRoutes('GET');
        $path   = trim($path, '/');
        $grup   = [];

        foreach ((new Menu())->panel as $item) {
            $boleh = array_filter($item['hak'], $akun->has(...)) !== []
                && (! isset($item['role']) || in_array($item['role'], $akun->roles(), true));

            // Pages of later phases have no route yet.
            if (! $boleh || ! isset($routes[$item['alamat']])) {
                continue;
            }

            $item['aktif'] = $path === $item['alamat'] || ($item['alamat'] !== 'panel' && str_starts_with($path, $item['alamat'] . '/'));
            $grup[$item['grup'] ?? ''][] = $item;
        }

        return $grup;
    }
}
