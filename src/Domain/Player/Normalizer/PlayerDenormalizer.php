<?php

declare(strict_types=1);

namespace App\Domain\Player\Normalizer;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class PlayerDenormalizer implements DenormalizerInterface
{
    public function __construct(private PlayerRepository $playerRepository)
    {
    }

    /** @param int $data */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ?Player
    {
        return $this->playerRepository->find($data);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool {
        return $type === Player::class && is_numeric($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['string' => true];
    }
}
