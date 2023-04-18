<?php

declare(strict_types=1);

namespace App\Domain;

interface KillerSerializerInterface
{
    /** @param array<string, mixed> $context */
    public function serialize(object $entity, array $context = [], string $format = 'json'): string;

    /**
     * @template T of object
     * @param class-string<T> $class
     * @param array<string, mixed> $context
     * @return T
     */
    public function deserialize(
        string $data,
        string $class,
        array $context = [],
        string $format = 'json',
    ): mixed;
}
