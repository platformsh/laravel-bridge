<?php
declare(strict_types=1);

namespace Platformsh\FlexBridge\Tests;

use PHPUnit\Framework\TestCase;

use function Platformsh\LaravelBridge\mapPlatformShEnvironment;
use function Platformsh\LaravelBridge\mapPlatformShDatabase;

class LaravelBridgeTest extends TestCase
{

    public function test_does_not_run_when_not_on_platformsh() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('APP_KEY'));
    }

    public function test_set_app_secret_if_not_set() : void
    {
        putenv('PLATFORM_APPLICATION=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');

        mapPlatformShEnvironment();

        $this->assertEquals('test', $_SERVER['APP_KEY']);
        $this->assertEquals('test', getenv('APP_KEY'));
    }

    public function test_dont_change_app_key_if_set() : void
    {
        putenv('PLATFORM_APPLICATION=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        putenv('APP_KEY=original');

        mapPlatformShEnvironment();

        $this->assertEquals('original', $_SERVER['APP_KEY']);
        $this->assertEquals('original', getenv('APP_KEY'));
    }

    public function test_session_secure_cookie_set_true_if_unset() : void
    {
        putenv('PLATFORM_APPLICATION=test');

        mapPlatformShEnvironment();

        $this->assertEquals('1', getenv('SESSION_SECURE_COOKIE'));
    }
}
