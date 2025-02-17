<?php

declare(strict_types=1);

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Config\DoctrineConfig;
use Symfony\Config\FrameworkConfig;

return static function (
    DoctrineConfig $doctrineConfig,
    FrameworkConfig $frameworkConfig,
    ContainerConfigurator $containerConfigurator,
): void {
    $doctrineConfig->dbal()->defaultConnection('default');
    $dbalConfig = $doctrineConfig->dbal()->connection('default');

    $dbalConfig
        ->url('%env(resolve:DATABASE_URL)%')
        ->profilingCollectBacktrace('%kernel.debug%');

    $doctrineConfig->orm()->defaultEntityManager('default');
    $entityManagerConfig = $doctrineConfig->orm()
        ->autoGenerateProxyClasses(true)
        ->enableLazyGhostObjects(true)
        ->entityManager('default');

    $entityManagerConfig
        ->reportFieldsWhereDeclared(true)
        ->validateXmlMapping(true)
        ->namingStrategy('doctrine.orm.naming_strategy.underscore_number_aware')
        ->autoMapping(true);

    $entityManagerConfig->mapping(Room::class)
        ->type('attribute')
        ->isBundle(false)
        ->dir('%kernel.project_dir%/src/Domain/Room/Entity')
        ->prefix('App\Domain\Room\Entity')
        ->alias('App\Domain\Room');

    $entityManagerConfig->mapping(Player::class)
        ->type('attribute')
        ->isBundle(false)
        ->dir('%kernel.project_dir%/src/Domain/Player/Entity')
        ->prefix('App\Domain\Player\Entity')
        ->alias('App\Domain\Player');

    $entityManagerConfig->mapping(Mission::class)
        ->type('attribute')
        ->isBundle(false)
        ->dir('%kernel.project_dir%/src/Domain/Mission/Entity')
        ->prefix('App\Domain\Mission\Entity')
        ->alias('App\Domain\Mission');

    if ($containerConfigurator->env() === 'test') {
        $dbalConfig->url('%env(resolve:DATABASE_URL_TEST)%');
    }

    if ($containerConfigurator->env() !== 'prod') {
        return;
    }

    $doctrineConfig->orm()
        ->autoGenerateProxyClasses(false)
        ->proxyDir('%kernel.build_dir%/doctrine/orm/Proxies');

    $entityManagerConfig->queryCacheDriver()
        ->type('pool')
        ->pool('doctrine.system_cache_pool');
    $entityManagerConfig->resultCacheDriver()
        ->type('pool')
        ->pool('doctrine.result_cache_pool');

    $frameworkConfig->cache()->pool('doctrine.result_cache_pool')->adapters(['cache.app']);
    $frameworkConfig->cache()->pool('doctrine.system_cache_pool')->adapters(['cache.system']);
};
