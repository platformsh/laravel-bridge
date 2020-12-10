<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeMailTest extends TestCase
{
    use RouteTestData;

    protected $config;

    /** @var string */
    protected $host;

    public function setUp(): void
    {
        parent::setUp();

        $this->host = 'smtp.platform.sh';
    }

    public function test_not_on_platformsh_does_nothing() : void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('MAIL_HOST'));
    }

    public function test_mail_gets_mapped() : void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        $this->loadDummyRoutes();

        putenv(sprintf('PLATFORM_SMTP_HOST=%s', $this->host));

        mapPlatformShEnvironment();

        $this->assertEquals('smtp', getenv('MAIL_DRIVER'));
        $this->assertEquals($this->host, getenv('MAIL_HOST'));
        $this->assertEquals('25', getenv('MAIL_PORT'));
        $this->assertEquals('0', getenv('MAIL_ENCRYPTION'));
    }
}
