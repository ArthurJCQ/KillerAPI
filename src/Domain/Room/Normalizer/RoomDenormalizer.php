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

    /**
     * @param string $data
     * @param array<string, string> $context
     */
    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): ?Room
    {
        return $this->roomRepository->find($data);
    }

    /** @param array<string, string> $context */
    public function supportsDenormalization(mixed $data, string $type, string $format = null, array $context = []): bool
    {
        return $type === Room::class && is_string($data);
    }

    /** @return array<'*'|'object'|string, bool|null> */
    public function getSupportedTypes(?string $format): array
    {
        return ['string' => true];
    }
}
