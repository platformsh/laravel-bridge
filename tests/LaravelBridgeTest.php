<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeTest extends TestCase
{
    use RouteTestData;

    public function test_does_not_run_when_not_on_platformsh() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('APP_KEY'));
    }

    public function test_set_app_secret_if_not_set() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=PI5YPW6P43NVU5WV4OU5DR2BHY5AEQYUCWE4BS2Y7N26Y4ZDKE6A====');
        $this->loadDummyRoutes();

        mapPlatformShEnvironment();

        $this->assertEquals('base64:PI5YPW6P43NVU5WV4OU5DR2BHY5AEQYUCWE4BS2Y7N0=', $_SERVER['APP_KEY']);
        $this->assertEquals('base64:PI5YPW6P43NVU5WV4OU5DR2BHY5AEQYUCWE4BS2Y7N0=', getenv('APP_KEY'));
    }

    public function test_dont_change_app_key_if_set() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=PI5YPW6P43NVU5WV4OU5DR2BHY5AEQYUCWE4BS2Y7N26Y4ZDKE6A====');
        putenv('APP_KEY=original');
        $this->loadDummyRoutes();

        mapPlatformShEnvironment();

        $this->assertEquals('original', $_SERVER['APP_KEY']);
        $this->assertEquals('original', getenv('APP_KEY'));
    }

    public function test_session_secure_cookie_set_true_if_unset() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        mapPlatformShEnvironment();

        $this->assertEquals('1', getenv('SESSION_SECURE_COOKIE'));
    }
}
