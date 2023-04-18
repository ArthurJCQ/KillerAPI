<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Api\Exception\KillerHttpException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\MapDecorated;
use Symfony\Component\Serializer\Normalizer\CacheableSupportsMethodInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

#[AsDecorator(decorates: ProblemNormalizer::class)]
readonly class KillerProblemNormalizer implements NormalizerInterface, CacheableSupportsMethodInterface
{
    public function __construct(#[MapDecorated] private ProblemNormalizer $inner)
    {
    }

    /**
     * @param array<string, string>|array<object> $context
     * @return float|int|bool|\ArrayObject|array<string, string>|string|null
     */
    public function normalize(
        mixed $object,
        string $format = null,
        array $context = [],
    ): float|int|bool|\ArrayObject|array|string|null {
        $normalizedException = $this->inner->normalize($object, $format, $context);

        if ($context['exception'] instanceof KillerHttpException) {
            $normalizedException['detail'] = $context['exception']->getMessage();
        }

        return $normalizedException;
    }

    public function supportsNormalization(mixed $data, string $format = null): bool
    {
        return $this->inner->supportsNormalization($data, $format);
    }

    public function hasCacheableSupportsMethod(): bool
    {
        return $this->inner->hasCacheableSupportsMethod();
    }
}
