<?php

declare(strict_types=1);

namespace App\Serializer;

use Symfony\Component\Serializer\SerializerInterface;

class KillerSerializer
{
    public function __construct(private readonly SerializerInterface $serializer)
    {
    }

    /** @param array<string, mixed> $context */
    public function serialize(object $entity, array $context = [], string $format = 'json'): string
    {
        return $this->serializer->serialize($entity, $format, $context);
    }

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
    ): mixed {
        /** @var T */
        return $this->serializer->deserialize(
            $data,
            $class,
            $format,
            $context,
        );
    }
}
