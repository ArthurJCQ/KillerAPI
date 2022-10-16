<?php

declare(strict_types=1);

namespace App\Validator;

use App\Entity\Player;
use App\Entity\Room;

use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PlayerCanJoinRoomValidator extends ConstraintValidator
{
    public function validate(mixed $player, Constraint $constraint): void
    {
        if (!$constraint instanceof PlayerCanJoinRoom) {
            throw new UnexpectedTypeException($constraint, PlayerCanJoinRoom::class);
        }

        if (!$player instanceof Player) {
            throw new UnexpectedTypeException($player, Player::class);
        }

        if (!$player->getRoom() || $player->getRoom()->getStatus() === Room::PENDING) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ user }}', $player->getName())
            ->setParameter('{{ room }}', $player->getRoom()->getCode())
            ->addViolation();
    }
}
