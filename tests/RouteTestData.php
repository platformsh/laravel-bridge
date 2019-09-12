<?php
declare(strict_types=1);

namespace Platformsh\LaravelBridge\Tests;

use function Platformsh\LaravelBridge\setEnvVar;

trait RouteTestData
{
    protected $routes = '
{
   "https://www.master-7rqtwti-gcpjkefjk4wc2.us-2.platformsh.site/" : {
      "original_url" : "https://www.{default}/",
      "attributes" : {},
      "type" : "upstream",
      "restrict_robots" : false,
      "tls" : {
         "client_authentication" : null,
         "min_version" : 771,
         "client_certificate_authorities" : [],
         "strict_transport_security" : {
            "include_subdomains" : null,
            "enabled" : true,
            "preload" : null
         }
      },
      "upstream" : "app",
      "cache" : {
         "enabled" : true,
         "headers" : [
            "Accept",
            "Accept-Language"
         ],
         "cookies" : [
            "/^SS?ESS.*/"
         ],
         "default_ttl" : 0
      },
      "http_access" : {
         "addresses" : [],
         "basic_auth" : {}
      },
      "primary" : true,
      "id" : "main",
      "ssi" : {
         "enabled" : false
      }
   }
}';

    protected function loadDummyRoutes() : void
    {
        $json = json_decode($this->routes, true);

        putenv('PLATFORM_ROUTES=' . $this->encode($json));
    }

    /**
     * @param mixed $value
     *
     * @return string
     */
    protected function encode($value) : string
    {
        return base64_encode(json_encode($value));
    }

}
