<?php
declare(strict_types=1);

namespace Platformsh\FlexBridge\Tests;

use PHPUnit\Framework\TestCase;

use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeRedisSessionTest extends TestCase
{

    protected $relationships;

    public function setUp()
    {
        parent::setUp();

        $this->relationships = [
            'redissession' => [
                [
                    'host' => 'redissession.internal',
                    'port' => '6379',
                    'scheme' => 'redis',
                ]
            ]
        ];
    }

    public function test_not_on_platformsh_does_not_set_cache() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
    }

    public function test_no_relationships_set_does_nothing() : void
    {
        // We assume no relationships array, but a PLATFORM_APPLICATION env var,
        // means we're in a build hook.

        putenv('PLATFORM_APPLICATION=test');

        //putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($this->relationships))));

        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
        $this->assertFalse(getenv('REDIS_PORT'));
    }

    public function test_no_redissession_relationship_set_does_nothing() : void
    {
        putenv('PLATFORM_APPLICATION=test');

        $rels = $this->relationships;
        unset($rels['redissession']);

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
        $this->assertFalse(getenv('REDIS_PORT'));
        $this->assertFalse(getenv('SESSION_DRIVER'));
    }

    public function test_rediscache_relationship_gets_mapped() : void
    {
        putenv('PLATFORM_APPLICATION=test');

        $rels = $this->relationships;

        putenv(sprintf('PLATFORM_RELATIONSHIPS=%s', base64_encode(json_encode($rels))));

        mapPlatformShEnvironment();

        $rel = $this->relationships['redissession'][0];

        $this->assertEquals($rel['host'], getenv('REDIS_HOST'));
        $this->assertEquals($rel['port'], getenv('REDIS_PORT'));
        $this->assertEquals('redis', getenv('SESSION_DRIVER'));
    }
}
