<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

interface PersistenceAdapterInterface
{
    public function flush(): void;

    public function clear(): void;
}
