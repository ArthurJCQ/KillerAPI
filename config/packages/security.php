<?php

declare(strict_types=1);

use App\Domain\Player\Entity\Player;
use Symfony\Config\SecurityConfig;

return static function (SecurityConfig $securityConfig): void {
    $securityConfig
        ->provider('app_user_provider')
        ->entity()
        ->class(Player::class)
        ->property('id');
    $securityConfig
        ->firewall('dev')
        ->pattern('^/(_(profiler|wdt)|css|images|js)/')
        ->security(false);
    $securityConfig
        ->firewall('public')
        ->pattern('^/player')
        ->methods(['POST'])
        ->stateless(true)
        ->lazy(true)
        ->provider('app_user_provider');
    $securityConfig
        ->firewall('main')
        ->stateless(true)
        ->lazy(true)
        ->provider('app_user_provider')
        ->jwt();
    $securityConfig->roleHierarchy('ROLE_ADMIN', ['ROLE_USER']);
    $securityConfig->roleHierarchy('ROLE_MASTER', ['ROLE_ADMIN']);
    $securityConfig->accessControl()->path('^/player')->roles('PUBLIC_ACCESS')->methods(['POST']);
    $securityConfig->accessControl()->path('^/player')->roles('ROLE_USER');
    $securityConfig->accessControl()->path('^/room')->roles('ROLE_USER');
    $securityConfig->accessControl()->path('^/mission')->roles('ROLE_USER');
};
