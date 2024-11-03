<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\MercureConfig;

return static function (MercureConfig $mercureConfig, ContainerConfigurator $containerConfigurator): void {
    $defaultHub = $mercureConfig->hub('default');
    $defaultHub
        ->url('%env(MERCURE_URL)%')
        ->publicUrl('%env(MERCURE_PUBLIC_URL)%');

    $jwtConfig = $defaultHub->jwt();
    $jwtConfig
        ->secret('%env(MERCURE_JWT_SECRET)%')
        ->publish('*')
        ->subscribe('*');

    if ($containerConfigurator->env() !== 'test') {
        return;
    }

    $defaultHub->url('mercure')->publicUrl('public-mercure');
    $jwtConfig->secret('secret')->publish('*');
};
