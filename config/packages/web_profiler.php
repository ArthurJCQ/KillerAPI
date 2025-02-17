<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\FrameworkConfig;
use Symfony\Config\WebProfilerConfig;

return static function (
    WebProfilerConfig $webProfilerConfig,
    FrameworkConfig $frameworkConfig,
    ContainerConfigurator $containerConfigurator,
): void {
    $profilerConfig = $frameworkConfig->profiler();

    if ($containerConfigurator->env() === 'dev') {
        $webProfilerConfig->toolbar(true);
        $webProfilerConfig->interceptRedirects(true);

        $profilerConfig->onlyExceptions(false);
        $profilerConfig->collectSerializerData(true);
    }

    if ($containerConfigurator->env() !== 'test') {
        return;
    }

    $webProfilerConfig->toolbar(false);
    $webProfilerConfig->interceptRedirects(false);

    $profilerConfig->collect(false);
};
