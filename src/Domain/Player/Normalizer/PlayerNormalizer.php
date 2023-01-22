<?php

declare(strict_types=1);

namespace App\Domain\Player\Normalizer;

use App\Domain\Player\Entity\Player;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

readonly class PlayerNormalizer implements NormalizerInterface
{
    public function __construct(private NormalizerInterface $normalizer)
    {
    }

    /**
     * @param array<string, string> $context
     * @return float|int|bool|\ArrayObject|array<string, string>|string|null
     */
    public function normalize(mixed $object, string $format = null, array $context = []): float|int|bool|\ArrayObject|array|string|null
    {
        if (!$object instanceof Player) {
            return [];
        }

        $normalizedPlayer = $this->normalizer->normalize($object, $format, $context);

        if (!isset($normalizedPlayer['target']) || !$object->getTarget()) {
            return $normalizedPlayer;
        }

        $normalizedPlayer['target'] = $normalizedPlayer['target']['id'];

        return $normalizedPlayer;
    }

    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Player;
    }
}
