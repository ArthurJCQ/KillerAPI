<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\TwigConfig;

return static function (TwigConfig $twigConfig, ContainerConfigurator $containerConfigurator): void {
    if ($containerConfigurator->env() !== 'test') {
        return;
    }

    $twigConfig->strictVariables(true);
};
