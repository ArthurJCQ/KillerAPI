<?php

declare(strict_types=1);

namespace App\Domain\Room\Validator;

use App\Domain\Room\Entity\Room;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class CanPatchRoomValidator extends ConstraintValidator
{
    public function validate(mixed $room, Constraint $constraint): void
    {
        if (!$constraint instanceof CanPatchRoom) {
            throw new UnexpectedTypeException($constraint, CanPatchRoom::class);
        }

        if (!$room instanceof Room) {
            throw new UnexpectedTypeException($room, Room::class);
        }

        if ($room->getStatus() !== Room::ENDED) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ room }}', $room->getName())
            ->addViolation();
    }
}
