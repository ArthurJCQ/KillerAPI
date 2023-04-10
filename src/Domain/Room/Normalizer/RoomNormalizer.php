<?php

declare(strict_types=1);

namespace App\Domain\Room\Normalizer;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\Specification\EnoughMissionInRoomSpecification;
use App\Domain\Room\Specification\EnoughPlayerInRoomSpecification;
use App\Domain\Room\Specification\AllPlayersAddedMissionSpecification;
use Symfony\Component\Serializer\Normalizer\NormalizerInterface;

final readonly class RoomNormalizer implements NormalizerInterface
{
    public function __construct(
        private NormalizerInterface $normalizer,
        private EnoughPlayerInRoomSpecification $enoughPlayerInRoomSpecification,
        private EnoughMissionInRoomSpecification $enoughMissionInRoomSpecification,
        private AllPlayersAddedMissionSpecification $allPlayersAddedMissionSpecification,
    ) {
    }

    /**
     * @param array<string, string> $context
     * @return float|int|bool|\ArrayObject|array<string, string>|string|null
     */
    public function normalize(
        mixed $object,
        string $format = null,
        array $context = [],
    ): float|int|bool|\ArrayObject|array|string|null {
        if (!$object instanceof Room) {
            return [];
        }

        $normalizedRoom = $this->normalizer->normalize($object, $format, $context);

        if (!is_array($normalizedRoom) || !isset($normalizedRoom['id'])) {
            return $normalizedRoom;
        }

        if (isset($normalizedRoom['players']) && \count($normalizedRoom['players']) > 0) {
            $normalizedRoom['players'] = array_values($normalizedRoom['players']);
        }

        $normalizedRoom['hasEnoughPlayers'] = $this->enoughPlayerInRoomSpecification->isSatisfiedBy($object);
        $normalizedRoom['hasEnoughMissions'] = $this->enoughMissionInRoomSpecification->isSatisfiedBy($object);
        $normalizedRoom['allPlayersAddedMissions'] = $this->allPlayersAddedMissionSpecification->isSatisfiedBy($object);

        return $normalizedRoom;
    }

    /** @param array<string, string> $context */
    public function supportsNormalization(mixed $data, string $format = null, array $context = []): bool
    {
        return $data instanceof Room;
    }
}
