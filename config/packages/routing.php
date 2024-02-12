<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig, ContainerConfigurator $containerConfigurator): void {
    $routerConfig = $frameworkConfig->router();

    $routerConfig->utf8(true);

    if ($containerConfigurator->env() !== 'prod') {
        return;
    }

    $routerConfig->strictRequirements(null);
};
