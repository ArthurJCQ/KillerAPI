<?php

declare(strict_types=1);

use Symfony\Config\DoctrineMigrationsConfig;

return static function (DoctrineMigrationsConfig $doctrineMigrationsConfig): void {
    $doctrineMigrationsConfig->migrationsPath('DoctrineMigrations', '%kernel.project_dir%/migrations');
    $doctrineMigrationsConfig->enableProfiler(false);
};
