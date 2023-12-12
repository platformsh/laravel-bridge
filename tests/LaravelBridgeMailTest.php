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

    /**
     * Test if the project has been instructed to disable Platform's own SMTP proxy
     * 
     * @return void
     */
    public function test_disabled_mail() : void
    {
        $smtpHost = 'smtp.external-service.com';
        $smtpPort = '587';
        $smtpMailer = 'sendmail';
        $smtpEncyption = 'tls';

        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        putenv('MAIL_HOST=' . $smtpHost);
        putenv('MAIL_MAILER=' . $smtpMailer);
        putenv('MAIL_PORT=' . $smtpPort);
        putenv('MAIL_ENCRYPTION=' . $smtpEncyption);
        $this->loadDummyRoutes();

        /**
         * https://support.platform.sh/hc/en-us/articles/12055076033810-Email-SMTP-sending-details
         * There is mixed information on the value of PLATFORM_SMTP_HOST the above support ticket 
         * states When outgoing emails are off, the variable is empty
         * Debugging on an instance shows it's set to false.
         * 
         * These assertions test both senarios
         */
        putenv(sprintf('PLATFORM_SMTP_HOST=%s', ''));
        mapPlatformShEnvironment();
        $this->assertEquals($smtpHost, getenv('MAIL_HOST'));
        $this->assertEquals($smtpMailer, getenv('MAIL_MAILER'));
        $this->assertEquals($smtpPort, getenv('MAIL_PORT'));
        $this->assertEquals($smtpEncyption, getenv('MAIL_ENCRYPTION'));

        putenv(sprintf('PLATFORM_SMTP_HOST=%s', false));
        mapPlatformShEnvironment();
        $this->assertEquals($smtpHost, getenv('MAIL_HOST'));
        $this->assertEquals($smtpMailer, getenv('MAIL_MAILER'));
        $this->assertEquals($smtpPort, getenv('MAIL_PORT'));
        $this->assertEquals($smtpEncyption, getenv('MAIL_ENCRYPTION'));

    }
}
