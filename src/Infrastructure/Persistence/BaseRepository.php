<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

/** @template T as object */
interface BaseRepository
{
    /** @param T $object */
    public function store(object $object): void;

    /** @param T $object */
    public function remove(object $object): void;

    /** @return ?T */
    public function find(int $id): ?object;

    /**
     * @param array<string, mixed> $options
     * @param array<string, string>|null $orderBy
     * @return T[]
     */
    public function findBy(array $options, ?array $orderBy = null, ?int $limit = null, ?int $offset = null): array;

    /**
     * @param array<string, mixed> $options
     * @param array<string, string>|null $orderBy
     * @return ?T
     */
    public function findOneBy(array $options, ?array $orderBy = null): ?object;
}
