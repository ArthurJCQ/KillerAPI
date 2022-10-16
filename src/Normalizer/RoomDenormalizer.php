<?php

declare(strict_types=1);

namespace App\Normalizer;

use App\Entity\Room;
use App\Repository\RoomRepository;
use Symfony\Component\Serializer\Normalizer\DenormalizerInterface;

class RoomDenormalizer implements DenormalizerInterface
{
    public function __construct(private readonly RoomRepository $roomRepository) {
    }

    public function denormalize(mixed $data, string $type, string $format = null, array $context = []): Room
    {
        return $this->roomRepository->findOneBy(['code' => $data]);
    }

    public function supportsDenormalization(mixed $data, string $type, string $format = null): bool
    {
        return $type === Room::class && is_string($data);
    }
}
