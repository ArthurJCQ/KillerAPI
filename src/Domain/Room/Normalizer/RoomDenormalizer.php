<?php

declare(strict_types=1);

namespace App\Domain\Room\Normalizer;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

final readonly class RoomDenormalizer implements DenormalizerInterface
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    /** @param string $data */
    public function denormalize(mixed $data, string $type, ?string $format = null, array $context = []): ?Room
    {
        return $this->roomRepository->find($data);
    }

    public function supportsDenormalization(
        mixed $data,
        string $type,
        ?string $format = null,
        array $context = [],
    ): bool {
        return $type === Room::class && is_string($data);
    }

    public function getSupportedTypes(?string $format): array
    {
        return ['string' => true];
    }
}
