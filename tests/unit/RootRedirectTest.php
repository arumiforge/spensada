<?php

use CodeIgniter\Test\CIUnitTestCase;
use CodeIgniter\Test\FeatureTestTrait;
use Config\Services;

/**
 * @internal
 */
final class RootRedirectTest extends CIUnitTestCase
{
    use FeatureTestTrait;

    protected function setUp(): void
    {
        // Other tests replace the shared route collection; start from the app's routes.
        Services::resetSingle('routes');
        Services::resetSingle('router');
        parent::setUp();
    }

    public function testRootRedirectsToLogin(): void
    {
        $this->get('/')->assertRedirectTo('https://example.com/login');
    }
}
