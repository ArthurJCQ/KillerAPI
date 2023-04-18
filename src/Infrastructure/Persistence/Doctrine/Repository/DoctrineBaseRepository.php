<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;

/** @template T as object */
abstract class DoctrineBaseRepository
{
    /** @var EntityRepository<T> */
    protected EntityRepository $repository;

    /** @param class-string<T> $class */
    public function __construct(protected EntityManagerInterface $entityManager, string $class)
    {
        $this->repository = $this->entityManager->getRepository($class);
    }

    /** @param T $object */
    public function store(object $object): void
    {
        $this->entityManager->persist($object);
    }

    /** @param T $object */
    public function remove(object $object): void
    {
        $this->entityManager->remove($object);
    }

    /** @return ?T */
    public function find(int|string $id): ?object
    {
        return $this->repository->find($id);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string>|null $orderBy
     * @return T[]
     */
    public function findBy(array $options, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array
    {
        return $this->repository->findBy($options, $orderBy, $limit, $offset);
    }

    /**
     * @param array<string, mixed> $options
     * @param array<string, string>|null $orderBy
     * @return ?T
     */
    public function findOneBy(array $options, ?array $orderBy = null): ?object
    {
        return $this->repository->findOneBy($options, $orderBy);
    }
}
