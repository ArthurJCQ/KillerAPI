<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsDecorator(decorates: ProblemNormalizer::class)]
readonly class KillerProblemNormalizer implements NormalizerInterface
{
    public const CUSTOM_ERROR_PREFIX = 'KILLER';

    public function __construct(#[AutowireDecorated] private ProblemNormalizer $inner)
    {
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = [],
    ): float|int|bool|\ArrayObject|array|string|null {
        $normalizedException = $this->inner->normalize($object, $format, $context);

        if ($context['exception'] instanceof HttpExceptionInterface) {
            $errorDetail = $context['exception']->getMessage();
        }

        if ($context['exception'] instanceof ValidationFailedException) {
            $errorDetail = $context['exception']->getValue();
        }

        if (isset($errorDetail) && is_string($errorDetail)) {
            $errorDetail = explode('_', $errorDetail, 2);
        }

        if (isset($errorDetail[0]) && $errorDetail[0] === self::CUSTOM_ERROR_PREFIX) {
            $normalizedException['detail'] = $errorDetail[1];
        }

        return $normalizedException;
    }

    public function supportsNormalization(mixed $data, ?string $format = null, array $context = []): bool
    {
        return $this->inner->supportsNormalization($data, $format);
    }

    public function getSupportedTypes(?string $format): array
    {
        return $this->inner->getSupportedTypes($format);
    }
}
