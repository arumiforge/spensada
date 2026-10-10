<?php

use App\Libraries\AkunAktif;
use App\Libraries\MenuPanel;
use App\Services\Akun\HakAkses;
use CodeIgniter\Router\Attributes\Filter;
use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\DatabaseTestTrait;
use CodeIgniter\Test\FeatureTestTrait;
use Config\HakAkses as Peta;
use Config\Menu;
use Config\Services;
use Tests\Support\AkunTrait;

/**
 * Panel menu shows only pages the roles may open (docs/15 L01-08, docs/08 UI-31, docs/09 §4.1, RT-21).
 *
 * @internal
 */
final class MenuPanelTest extends CIUnitTestCase
{
    use AkunTrait;
    use DatabaseTestTrait;
    use FeatureTestTrait;

    protected $refresh   = true;
    protected $namespace = 'App';

    protected function setUp(): void
    {
        Services::resetSingle('routes');
        Services::resetSingle('router');
        Services::resetSingle('request');
        Services::resetSingle('response');
        parent::setUp();
    }

    public function testEachRoleSeesOnlyItsPages(): void
    {
        $this->assertSame(
            [
                ''                 => ['Dashboard hari ini'],
                'Siswa'            => ['Data siswa', 'Penempatan kelas', 'Akun siswa', 'Atribut tambahan'],
                'Sekolah'          => ['Identitas sekolah', 'Tahun ajaran', 'Kelas dan wali kelas'],
                'Akun dan stasiun' => ['Akun staf', 'Log aktivitas', 'Pemeriksaan sistem'],
            ],
            $this->labels(['staf', 'admin']),
        );

        foreach (['staf', 'guru_piket', 'guru_bk', 'pimpinan'] as $role) {
            $this->assertSame(['' => ['Dashboard hari ini']], $this->labels(array_unique(['staf', $role])), $role);
        }
    }

    public function testCurrentPageIsMarked(): void
    {
        $akun = new AkunAktif();
        $akun->set(['id' => 1], ['staf', 'admin']);
        $menu = (new MenuPanel())->untuk($akun, '/panel/akun-staf/3/ubah');

        $this->assertFalse($menu[''][0]['aktif']);
        $this->assertTrue($menu['Akun dan stasiun'][0]['aktif']);
    }

    /**
     * Every role that sees an item passes the `hak` attribute of the page it opens,
     * and the items follow docs/09 §4.1 (one address per page, known rights).
     */
    public function testMenuRightsMatchTheTargetPages(): void
    {
        $routes  = service('routes')->loadRoutes()->getRoutes('GET');
        $hakAkses = new HakAkses();
        $roles   = array_unique(array_merge(...array_values(array_map('array_keys', (new Peta())->hak))));

        foreach ((new Menu())->panel as $item) {
            $this->assertSame([], array_diff($item['hak'], array_keys((new Peta())->hak)), $item['label']);

            if (! isset($routes[$item['alamat']])) {
                continue;
            }

            [$class, $method] = explode('::', ltrim($routes[$item['alamat']], '\\'));

            foreach ((new ReflectionMethod($class, $method))->getAttributes(Filter::class) as $attribute) {
                $needed = $attribute->newInstance()->having;

                foreach ($roles as $role) {
                    $seesItem = array_filter($item['hak'], static fn (string $hak): bool => $hakAkses->has([$role], $hak)) !== [];
                    $opens    = array_filter($needed, static fn (string $hak): bool => $hakAkses->has([$role], $hak)) !== [];
                    $this->assertFalse($seesItem && ! $opens, "{$item['label']}: {$role} sees the item but cannot open it");
                }
            }
        }
    }

    public function testPanelPageShowsMenuAndAccountMenu(): void
    {
        $admin  = $this->buatAkun(['nama' => 'Rina Wulandari', 'username' => 'rina.w'], ['admin']);
        $result = $this->withSession($this->sesiAkun($admin))->get('panel');

        $result->assertOK();
        $result->assertSee('Halaman ini sedang disiapkan.');
        $result->assertSeeElement('a[aria-current=page]');
        $result->assertSee('Akun staf', 'a');
        $result->assertSee('Rina Wulandari', '.menu-akun-nama');
        $result->assertSee('Admin', '.menu-akun-role');
        $this->assertStringContainsString('action="' . url_to('akun.login.keluar') . '"', $result->getBody());
        $result->assertSee('Ganti password', 'a');
    }

    /**
     * @param list<string> $roles
     *
     * @return array<string, list<string>>
     */
    private function labels(array $roles): array
    {
        $akun = new AkunAktif();
        $akun->set(['id' => 1], $roles);

        return array_map(static fn (array $items): array => array_column($items, 'label'), (new MenuPanel())->untuk($akun, '/panel'));
    }
}
