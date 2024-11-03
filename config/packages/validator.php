<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig, ContainerConfigurator $containerConfigurator): void {
    $validationConfig = $frameworkConfig->validation();

    $validationConfig->emailValidationMode('html5');

    if ($containerConfigurator->env() !== 'test') {
        return;
    }

    $validationConfig->notCompromisedPassword()->enabled(false);
};
