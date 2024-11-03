<?php

declare(strict_types=1);

use Symfony\Config\NelmioCorsConfig;

return static function (NelmioCorsConfig $nelmioCorsConfig): void {
    $nelmioCorsConfig->defaults()
        ->allowCredentials(true)
        ->originRegex(true)
        ->allowOrigin(['%env(CORS_ALLOW_ORIGIN)%'])
        ->allowMethods(['GET', 'OPTIONS', 'POST', 'PUT', 'PATCH', 'DELETE'])
        ->allowHeaders(['Content-Type', 'Authorization'])
        ->exposeHeaders(['Link'])
        ->maxAge(3600);
};
