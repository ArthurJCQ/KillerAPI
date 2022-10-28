<?php

declare(strict_types=1);

namespace App\Domain\Player\Validator;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use Symfony\Component\Form\Exception\UnexpectedTypeException;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;

class PlayerCanUpdateMissionValidator extends ConstraintValidator
{
    public function __construct(private Security $security)
    {
    }

    public function validate(mixed $mission, Constraint $constraint): void
    {
        /** @var Player $player */
        $player = $this->security->getUser();

        if (!$constraint instanceof PlayerCanUpdateMission) {
            throw new UnexpectedTypeException($constraint, UnexpectedTypeException::class);
        }

        if (!$mission instanceof Mission) {
            throw new UnexpectedTypeException($mission, Mission::class);
        }

        if ($mission->getAuthor() === $player) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ user }}', $player->getName())
            ->addViolation();
    }
}
