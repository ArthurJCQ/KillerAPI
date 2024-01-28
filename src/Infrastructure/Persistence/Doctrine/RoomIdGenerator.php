<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine;

use App\Domain\Room\Entity\Room;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Id\AbstractIdGenerator;
use Marvin255\RandomStringGenerator\Generator\RandomStringGenerator;

class RoomIdGenerator extends AbstractIdGenerator
{
    public function __construct(private readonly RandomStringGenerator $randomStringGenerator)
    {
    }

    /** @inheritdoc */
    public function generateId(EntityManagerInterface $em, $entity): string
    {
        if (!$entity instanceof Room) {
            throw new \LogicException('Can not use RoomIdGenerator for non-room object.');
        }

        $roomCode = $this->randomStringGenerator->alphanumeric(5);

        if ($em->getRepository(Room::class)->findOneBy(['id' => $roomCode]) instanceof Room) {
            return $this->generateId($em, $entity);
        }

        return strtoupper($roomCode);
    }
}
