<?php

declare(strict_types=1);

namespace Platformsh\LaravelBridge;

use Platformsh\ConfigReader\Config;

mapPlatformShEnvironment();

/**
 * Map Platform.Sh environment variables to the values Laravel expects.
 *
 * This is wrapped up into a function to avoid executing code in the global
 * namespace.
 */
function mapPlatformShEnvironment() : void
{
    $config = new Config();

    if (!$config->inRuntime()) {
        return;
    }

    // Laravel needs an accurate base URL.
    mapAppUrl($config);

    // Set the application secret if it's not already set.
    $secret = getenv('APP_KEY');
    if (!$secret && $config->projectEntropy) {
        $secret = "base64:" . base64_encode(substr(base64_decode($config->projectEntropy), 0, 32));
    }
    // This value must always be defined, even if it's set to false/empty.
    setEnvVar('APP_KEY', $secret);

    // Force secure cookies on by default, since Platform.sh is SSL-all-the-things.
    // It can be overridden explicitly.
    $secure_cookie = getenv('SESSION_SECURE_COOKIE') ?: 1;
    setEnvVar('SESSION_SECURE_COOKIE', $secure_cookie);

    // Map services as feasible.
    mapPlatformShDatabase('database', $config);
    mapPlatformShRedisCache('rediscache', $config);
    mapPlatformShRedisSession('redissession', $config);
    mapPlatformShMail($config);

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

function mapAppUrl(Config $config) : void
{
    // If the APP_URL is already set, leave it be.
    if (getenv('APP_URL')) {
        return;
    }

    // If not on Platform.sh, say in a local dev environment, simply
    // do nothing.  Users need to set the host pattern themselves
    // in a .env file.
    if (!$config->inRuntime()) {
        return;
    }

    $routes = $config->getUpstreamRoutes($config->applicationName);

    if (!count($routes)) {
        return;
    }

    $requestUrl = chr(0);
    if (isset($_SERVER['SERVER_NAME'])) {
        $requestUrl = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? 'https://' : 'http://')
            . $_SERVER['SERVER_NAME'];
    }

    usort($routes, function (array $a, array $b) use ($requestUrl) {
        // false sorts before true, normally, so negate the comparison.
        return
            [strpos($a['url'], $requestUrl) !== 0, !$a['primary'], strpos($a['url'], 'https://') !== 0, strlen($a['url'])]
            <=>
            [strpos($b['url'], $requestUrl) !== 0, !$b['primary'], strpos($b['url'], 'https://') !== 0, strlen($b['url'])];
    });

    setEnvVar('APP_URL', reset($routes)['url'] ?: null);
}

function mapPlatformShDatabase(string $relationshipName, Config $config) : void
{
    if (!$config->hasRelationship($relationshipName)) {
        return;
    }

    $credentials = $config->credentials($relationshipName);

    setEnvVar('DB_CONNECTION', $credentials['scheme']);
    setEnvVar('DB_HOST', $credentials['host']);
    setEnvVar('DB_PORT', $credentials['port']);
    setEnvVar('DB_DATABASE', $credentials['path']);
    setEnvVar('DB_USERNAME', $credentials['username']);
    setEnvVar('DB_PASSWORD', $credentials['password']);
}

function mapPlatformShRedisCache(string $relationshipName, Config $config) : void
{
    if (!$config->hasRelationship($relationshipName)) {
        return;
    }

    $credentials = $config->credentials($relationshipName);

    setEnvVar('CACHE_DRIVER', 'redis');
    setEnvVar('REDIS_CLIENT', 'phpredis');
    setEnvVar('REDIS_HOST', $credentials['host']);
    setEnvVar('REDIS_PORT', $credentials['port']);
}

function mapPlatformShRedisSession(string $relationshipName, Config $config) : void
{
    if (!$config->hasRelationship($relationshipName)) {
        return;
    }

    $credentials = $config->credentials($relationshipName);

    setEnvVar('SESSION_DRIVER', 'redis');
    setEnvVar('REDIS_CLIENT', 'phpredis');
    setEnvVar('REDIS_HOST', $credentials['host']);
    setEnvVar('REDIS_PORT', $credentials['port']);
}

function mapPlatformShMail(Config $config) : void
{
    if (!isset($config->smtpHost)) {
        return;
    }

    setEnvVar('MAIL_DRIVER', 'smtp');
    setEnvVar('MAIL_MAILER', 'smtp'); // From laravel 7 onwards MAIL_DRIVER is renamed to MAIL_MAILER
    setEnvVar('MAIL_HOST', $config->smtpHost);
    setEnvVar('MAIL_PORT', '25');
    setEnvVar('MAIL_ENCRYPTION', '0');
}
