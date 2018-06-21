<?php

declare(strict_types=1);

namespace Platformsh\LaravelBridge;

mapPlatformShEnvironment();

/**
 * Map Platform.Sh environment variables to the values Laravel expects.
 *
 * This is wrapped up into a function to avoid executing code in the global
 * namespace.
 */
function mapPlatformShEnvironment() : void
{
    // If this env var is not set then we're not on a Platform.sh
    // environment or in the build hook, so don't try to do anything.
    if (!getenv('PLATFORM_APPLICATION')) {
        return;
    }

    // Laravel needs an accurate base URL.
    mapAppUrl();

    // Set the application secret if it's not already set.
    $secret = getenv('APP_KEY') ?: getenv('PLATFORM_PROJECT_ENTROPY') ?: null;
    setEnvVar('APP_KEY', $secret);

    // Force secure cookies on by default, since Platform.sh is SSL-all-the-things.
    // It can be overridden explicitly.
    $secure_cookie = getenv('SESSION_SECURE_COOKIE') ?: 1;
    setEnvVar('SESSION_SECURE_COOKIE', $secure_cookie);

    // Decode the relationships array once for performance. It's not quite as encapsulated
    // but performance wins here.
    if (getenv('PLATFORM_RELATIONSHIPS')) {
        $relationships = json_decode(base64_decode(getenv('PLATFORM_RELATIONSHIPS'), true), true);

        mapPlatformShDatabase('database', $relationships);

        mapPlatformShRedisCache('rediscache', $relationships);

        mapPlatformShRedisSession('redissession', $relationships);
    }

    // @TODO Should MAIL_* be set as well?

    // @TODO Should we support a redisqueue service as well?

}

/**
 * Sets an environment variable in all the myriad places PHP can store it.
 *
 * @param string $name
 *   The name of the variable to set.
 * @param mixed $value
 *   The value to set.  Null to unset it.
 */
function setEnvVar(string $name, $value = null) : void
{
    if (!putenv("$name=$value")) {
        throw new \RuntimeException('Failed to create environment variable: ' . $name);
    }
    $order = ini_get('variables_order');
    if (stripos($order, 'e') !== false) {
        $_ENV[$name] = $value;
    }
    if (stripos($order, 's') !== false) {
        if (strpos($name, 'HTTP_') !== false) {
            throw new \RuntimeException('Refusing to add ambiguous environment variable ' . $name . ' to $_SERVER');
        }
        $_SERVER[$name] = $value;
    }
}

function mapAppUrl() : void
{
    // If the APP_URL is already set, leave it be.
    if (getenv('APP_URL')) {
        return;
    }

    if (!getenv('PLATFORM_ROUTES')) {
        return;
    }

    $routes = json_decode(base64_decode(getenv('PLATFORM_ROUTES')), TRUE);
    $settings['trusted_host_patterns'] = [];
    foreach ($routes as $url => $route) {
        $host = parse_url($url, PHP_URL_HOST);
        // This conditional translates to "if it's the route for this app".
        // Note: wildcard routes are not currently supported by this code.
        if ($host !== FALSE && $route['type'] == 'upstream' && $route['upstream'] == getenv('PLATFORM_APPLICATION_NAME')) {
            setEnvVar('APP_URL', $url);
            return;
        }
    }
}

function mapPlatformShDatabase(string $relationshipName, array $relationships) : void
{
    if (isset($relationships[$relationshipName])) {
        foreach ($relationships[$relationshipName] as $endpoint) {
            if (empty($endpoint['query']['is_master'])) {
                continue;
            }

            setEnvVar('DB_CONNECTION', $endpoint['scheme']);
            setEnvVar('DB_HOST', $endpoint['host']);
            setEnvVar('DB_PORT', $endpoint['port']);
            setEnvVar('DB_DATABASE', $endpoint['path']);
            setEnvVar('DB_USERNAME', $endpoint['username']);
            setEnvVar('DB_PASSWORD', $endpoint['password']);
        }
    }
}

function mapPlatformShRedisCache(string $relationshipName, array $relationships) : void
{
    if (isset($relationships[$relationshipName])) {
        setEnvVar('CACHE_DRIVER', 'redis');
        foreach ($relationships[$relationshipName] as $endpoint) {
            setEnvVar('REDIS_HOST', $endpoint['host']);
            setEnvVar('REDIS_PORT', $endpoint['port']);
            break;
        }
    }
}

function mapPlatformShRedisSession(string $relationshipName, array $relationships) : void
{
    if (isset($relationships[$relationshipName])) {
        setEnvVar('SESSION_DRIVER', 'redis');
        foreach ($relationships[$relationshipName] as $endpoint) {
            setEnvVar('REDIS_HOST', $endpoint['host']);
            setEnvVar('REDIS_PORT', $endpoint['port']);
            break;
        }
    }
}
