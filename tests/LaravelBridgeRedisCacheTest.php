<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeRedisCacheTest extends TestCase
{
    use RouteTestData;

    protected $relationships;

    public function setUp(): void
    {
        parent::setUp();

        $this->relationships = [
            'rediscache' => [
                [
                    'host' => 'rediscache.internal',
                    'port' => '6379',
                    'scheme' => 'redis',
                ]
            ]
        ];
    }

    public function test_not_on_platformsh_does_nothing() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
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

        $this->assertFalse(getenv('REDIS_HOST'));
        $this->assertFalse(getenv('REDIS_PORT'));
    }

    public function test_no_rediscache_relationship_set_does_nothing() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        $rels = $this->relationships;
        unset($rels['rediscache']);

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
        $this->assertFalse(getenv('REDIS_PORT'));
        $this->assertFalse(getenv('CACHE_DRIVER'));
    }

    public function test_rediscache_relationship_gets_mapped() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        $rels = $this->relationships;

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $rel = $this->relationships['rediscache'][0];

        $this->assertEquals('redis', getenv('CACHE_DRIVER'));
        $this->assertEquals('phpredis', getenv('REDIS_CLIENT'));
        $this->assertEquals($rel['host'], getenv('REDIS_HOST'));
        $this->assertEquals($rel['port'], getenv('REDIS_PORT'));
    }
}
