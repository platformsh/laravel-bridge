<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use PHPUnit\Framework\TestCase;
use function Platformsh\LaravelBridge\mapPlatformShEnvironment;

class LaravelBridgeAppUrlTest extends TestCase
{
    protected $routes = [
        'https://one.example.com' => [
            'type' => 'upstream',
            'upstream' => 'app1',
        ],
        'https://redirect.example.com' => [
            'type' => 'redirect',
            'to' => 'https://one.example.com',
        ],
        'https://two.example.com' => [
            'type' => 'upstream',
            'upstream' => 'app2',
        ],
    ];

    public function test_not_on_platformsh_does_nothing(): void
    {
        mapPlatformShEnvironment();

        $this->assertFalse(getenv('REDIS_HOST'));
    }

    public function test_app_url_already_set_does_nothing(): void
    {
        // We assume no routes array, but a PLATFORM_APPLICATION env var,
        // means we're in a build hook.

        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');
        putenv('APP_URL=current');

        mapPlatformShEnvironment();

        $this->assertEquals('current', getenv('APP_URL'));
    }

    /**
     * @dataProvider getRoutes
     */
    public function test_app_url_set(string $url, string $appName): void
    {
        putenv('PLATFORM_APPLICATION_NAME=test');
        putenv('PLATFORM_ENVIRONMENT=test');
        putenv('PLATFORM_PROJECT_ENTROPY=test');

        putenv(sprintf('PLATFORM_ROUTES=%s', base64_encode(json_encode($this->routes))));
        putenv(sprintf('PLATFORM_APPLICATION_NAME=%s', $appName));

        mapPlatformShEnvironment();

        $this->assertEquals($url, getenv('APP_URL'));
    }

    public function getRoutes(): iterable
    {
        foreach ($this->routes as $url => $route) {
            if ($route['type'] == 'upstream') {
                yield [$url, $route['upstream']];
            }
        }
    }
}
