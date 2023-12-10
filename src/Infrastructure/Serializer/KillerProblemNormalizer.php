<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Api\Exception\KillerHttpException;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;

#[AsDecorator(decorates: ProblemNormalizer::class)]
readonly class KillerProblemNormalizer implements NormalizerInterface
{
    public function __construct(#[AutowireDecorated] private ProblemNormalizer $inner)
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

    /** @param array<string, string>|array<object> $context */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format);
    }

    /** @return array<'*'|'object'|string, bool|null> */
    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
