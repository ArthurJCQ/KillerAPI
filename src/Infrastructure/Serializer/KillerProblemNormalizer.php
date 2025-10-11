<?php

declare(strict_types=1);

namespace App\Infrastructure\Serializer;

use App\Domain\KillerExceptionInterface;
use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Serializer\SerializerAwareInterface;
use Symfony\Component\Serializer\SerializerAwareTrait;
use Symfony\Component\Validator\Exception\ValidationFailedException;

#[AsDecorator(decorates: ProblemNormalizer::class)]
class KillerProblemNormalizer implements NormalizerInterface, SerializerAwareInterface
{
    use SerializerAwareTrait;

    public const string CUSTOM_ERROR_PREFIX = 'KILLER';

    public function __construct(#[AutowireDecorated] private ProblemNormalizer $inner)
    {
    }

    public function normalize(
        mixed $object,
        ?string $format = null,
        array $context = [],
    ): float|int|bool|\ArrayObject|array|string|null {
        if (!$this->serializer) {
            throw new \LogicException('The serializer must be set.');
        }

        $this->inner->setSerializer($this->serializer);
        $normalizedException = $this->inner->normalize($object, $format, $context);

        if ($context['exception'] instanceof KillerExceptionInterface) {
            $normalizedException['detail'] = $context['exception']->getMessage();
        }

        if ($context['exception'] instanceof ValidationFailedException) {
            $errors = $context['exception']->getViolations();
            $normalizedException['detail'] = $errors->get(0)->getMessage();
        }

        if ($context['exception'] instanceof HttpExceptionInterface) {
            $errorDetail = $context['exception']->getMessage();
            $errorDetail = explode('_', $errorDetail, 2);

            if (isset($errorDetail[0]) && $errorDetail[0] === self::CUSTOM_ERROR_PREFIX) {
                $normalizedException['detail'] = $errorDetail[1];
            }
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
