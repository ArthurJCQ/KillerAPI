<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Domain\KillerSerializerInterface;
use Symfony\Component\Serializer\SerializerInterface;

readonly class KillerSerializer implements KillerSerializerInterface
{
    public function __construct(private SerializerInterface $serializer)
    {
    }

    public function serialize(object $entity, array $context = [], string $format = 'json'): string
    {
        return $this->serializer->serialize($entity, $format, $context);
    }

    /**
     * @template T of object
     * @param class-string<T> $class
     * @return T
     */
    public function deserialize(
        string $data,
        string $class,
        array $context = [],
        string $format = 'json',
    ): mixed {
        return $this->serializer->deserialize(
            $data,
            $class,
            $format,
            $context,
        );
    }
}
