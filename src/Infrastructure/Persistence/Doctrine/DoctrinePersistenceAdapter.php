<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Doctrine\ORM\EntityManagerInterface;

class DoctrinePersistenceAdapter implements PersistenceAdapterInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function flush(): void
    {
        $this->entityManager->flush();
    }

    public function clear(): void
    {
        $this->entityManager->clear();
    }
}
