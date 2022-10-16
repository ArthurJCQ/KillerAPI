<?php

declare(strict_types=1);

namespace App\Persistence;

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
