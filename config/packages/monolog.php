<?php

declare(strict_types=1);

use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\MonologConfig;

return static function (MonologConfig $monologConfig, ContainerConfigurator $containerConfigurator): void {
    $monologConfig->channels(['deprecation']);

    if ($containerConfigurator->env() === 'dev') {
        $monologConfig->handler('main')
            ->type('stream')
            ->path('%kernel.logs_dir%/%kernel.environment%.log')
            ->level('debug')
            ->channels(['elements' => ['!event']]);

        $monologConfig->handler('console')
            ->type('console')
            ->processPsr3Messages(false)
            ->channels(['elements' => ['!event', '!doctrine', '!console']]);
    }

    if ($containerConfigurator->env() === 'test') {
        $mainHandler = $monologConfig->handler('main')
            ->type('fingers_crossed')
            ->actionLevel('error')
            ->handler('nested');

        $mainHandler->channels(['elements' => ['!event']]);
        $mainHandler->excludedHttpCode()->code(404);
        $mainHandler->excludedHttpCode()->code(405);

        $monologConfig->handler('nested')
            ->type('stream')
            ->path('%kernel.logs_dir%/%kernel.environment%.log')
            ->level('debug');
    }

    if ($containerConfigurator->env() !== 'prod') {
        return;
    }

    $mainHandler = $monologConfig->handler('main')
        ->type('fingers_crossed')
        ->actionLevel('error')
        ->bufferSize(50)
        ->handler('nested');

    $mainHandler->excludedHttpCode([404, 405]);

    $monologConfig->handler('nested')
        ->type('rotating_file')
        ->path('%kernel.logs_dir%/%kernel.environment%.log')
        ->level('info')
        ->maxFiles(10);

    $monologConfig->handler('console')
        ->type('console')
        ->processPsr3Messages(false)
        ->channels(['elements' => ['!event', '!doctrine']]);

    $monologConfig->handler('deprecation')
        ->type('stream')
        ->path('php://stderr')
        ->channels(['elements' => ['deprecation']]);
};
