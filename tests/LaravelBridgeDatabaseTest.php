<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeDatabaseTest extends TestCase
{
    use RouteTestData;

    protected $relationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->relationships = [
            'database' => [
                [
                    'scheme' => 'mysql',
                    'username' => 'user',
                    'password' => '',
                    'host' => 'database.internal',
                    'port' => '3306',
                    'path' => 'main',
                    'query' => ['is_master' => true],
                ]
            ]
        ];
    }

    public function test_not_on_platformsh_does_nothing() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('DB_DATABASE'));
    }

    public function test_no_relationships_set_does_nothing() : void
    {
        // We assume no relationships array, but a PLATFORM_APPLICATION env var,
        // means we're in a build hook.

        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        //putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($this->relationships))));

        mapPlatformShEnvironment();

        $this->assertFalse(getenv('DB_CONNECTION'));
        $this->assertFalse(getenv('DB_HOST'));
        $this->assertFalse(getenv('DB_PORT'));
        $this->assertFalse(getenv('DB_DATABASE'));
        $this->assertFalse(getenv('DB_USERNAME'));
        $this->assertFalse(getenv('DB_PASSWORD'));
    }

    public function test_no_database_relationship_set_does_nothing() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        $rels = $this->relationships;
        unset($rels['database']);

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $this->assertFalse(getenv('DB_CONNECTION'));
        $this->assertFalse(getenv('DB_HOST'));
        $this->assertFalse(getenv('DB_PORT'));
        $this->assertFalse(getenv('DB_DATABASE'));
        $this->assertFalse(getenv('DB_USERNAME'));
        $this->assertFalse(getenv('DB_PASSWORD'));
    }

    public function test_database_relationship_gets_mapped() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        $rels = $this->relationships;

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $rel = $this->relationships['database'][0];

        $this->assertEquals($rel['scheme'], getenv('DB_CONNECTION'));
        $this->assertEquals($rel['host'], getenv('DB_HOST'));
        $this->assertEquals($rel['port'], getenv('DB_PORT'));
        $this->assertEquals($rel['path'], getenv('DB_DATABASE'));
        $this->assertEquals($rel['username'], getenv('DB_USERNAME'));
        $this->assertEquals($rel['password'], getenv('DB_PASSWORD'));
    }
}
