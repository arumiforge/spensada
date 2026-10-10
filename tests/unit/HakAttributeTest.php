<?php

use CodeIgniter\Router\Attributes\Filter;
use CodeIgniter\Test\CIUnitTestCase;
use Config\HakAkses;
use Config\Routing;
use Tests\Support\Controllers\HakFixture;

/**
 * Every public controller method in the panel, portal and kiosk areas, plus
 * change password and logout, carries a `hak` attribute with known rights
 * (docs/09 RT-02, docs/07 ARS-13, §5.2).
 *
 * @internal
 */
final class HakAttributeTest extends CIUnitTestCase
{
    public function testControllerAttributesAreRead(): void
    {
        // Without this, CI4 ignores every #[Filter] attribute.
        $this->assertTrue(config(Routing::class)->useControllerAttributes);
    }

    public function testEveryAreaMethodHasHak(): void
    {
        $classes = [];

        foreach (['Panel', 'Portal', 'Kiosk'] as $area) {
            $classes = [...$classes, ...$this->controllersIn(APPPATH . "Controllers/{$area}", "App\\Controllers\\{$area}")];
        }

        $problems = [];

        foreach ($classes as $class) {
            $problems = [...$problems, ...$this->problems($class)];
        }

        // Change password and logout (RT-02), once they exist; the login page itself has no `hak`.
        if (class_exists('App\Controllers\Akun\Password')) {
            $problems = [...$problems, ...$this->problems('App\Controllers\Akun\Password')];
        }
        if (method_exists('App\Controllers\Akun\Login', 'keluar')) {
            $problems = [...$problems, ...$this->problems('App\Controllers\Akun\Login', ['keluar'])];
        }

        // Passes with no area controllers yet (docs/15 L00-08).
        $this->assertSame([], $problems);
    }

    public function testCheckFindsMissingAndUnknownRights(): void
    {
        $this->assertSame(
            [
                HakFixture::class . '::unguarded has no hak attribute',
                HakFixture::class . '::unknownRight names unknown right HA-TIDAK-ADA',
            ],
            $this->problems(HakFixture::class),
        );
    }

    /**
     * @param list<string>|null $only method names to check, or null for all
     *
     * @return list<string>
     */
    private function problems(string $class, ?array $only = null): array
    {
        $known    = (new HakAkses())->hak;
        $problems = [];

        foreach ((new ReflectionClass($class))->getMethods(ReflectionMethod::IS_PUBLIC) as $method) {
            // Methods of BaseController and CodeIgniter\Controller are not routes.
            if ($method->isStatic() || $method->isConstructor() || $method->class !== $class
                || ($only !== null && ! in_array($method->name, $only, true))) {
                continue;
            }

            $name   = "{$class}::{$method->name}";
            $rights = [];

            foreach ($method->getAttributes(Filter::class) as $attribute) {
                $filter = $attribute->newInstance();
                if ($filter->by === 'hak') {
                    $rights[] = $filter->having;
                }
            }

            if ($rights === [] || in_array([], $rights, true)) {
                $problems[] = "{$name} has no hak attribute";
            }

            foreach (array_diff(array_merge(...$rights), array_keys($known)) as $hak) {
                $problems[] = "{$name} names unknown right {$hak}";
            }
        }

        return $problems;
    }

    /**
     * @return list<class-string>
     */
    private function controllersIn(string $dir, string $namespace): array
    {
        if (! is_dir($dir)) {
            return [];
        }

        $classes = [];

        foreach (new RecursiveIteratorIterator(new RecursiveDirectoryIterator($dir, FilesystemIterator::SKIP_DOTS)) as $file) {
            if ($file->getExtension() === 'php') {
                $relative  = substr($file->getPathname(), strlen($dir) + 1, -4);
                $classes[] = $namespace . '\\' . str_replace(DIRECTORY_SEPARATOR, '\\', $relative);
            }
        }

        return $classes;
    }
}
