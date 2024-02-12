<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig, ContainerConfigurator $containerConfigurator): void {
    $frameworkConfig->secret('%env(APP_SECRET)%');
    $frameworkConfig->csrfProtection()->enabled(false);
    $frameworkConfig->annotations()->enabled(false);
    $frameworkConfig->httpMethodOverride(false);
    $frameworkConfig->handleAllThrowables(true);
    $frameworkConfig->session()->enabled(false);
    $frameworkConfig->phpErrors()->log(true);

    if ($containerConfigurator->env() !== 'test') {
        return;
    }

    $frameworkConfig->test(true);
};
