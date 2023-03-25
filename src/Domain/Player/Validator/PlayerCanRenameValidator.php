<?php

declare(strict_types=1);

namespace App\Domain\Player\Validator;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use Symfony\Component\Validator\Constraint;
use Symfony\Component\Validator\ConstraintValidator;
use Symfony\Component\Validator\Exception\UnexpectedTypeException;

class PlayerCanRenameValidator extends ConstraintValidator
{
    public function __construct(private readonly PlayerRepository $playerRepository)
    {
    }

    public function validate(mixed $player, Constraint $constraint): void
    {
        if (!$constraint instanceof PlayerCanRename) {
            throw new UnexpectedTypeException($constraint, PlayerCanRename::class);
        }

        if (!$player instanceof Player) {
            throw new UnexpectedTypeException($player, Player::class);
        }

        if ($player->getRoom() === null) {
            return;
        }

        $players = $this->playerRepository->findPlayersByRoomAndName($player->getRoom(), $player->getName());

        if (\count($players) === 0 || $players[0]->getId() === $player->getId()) {
            return;
        }

        $this->context->buildViolation($constraint->message)
            ->setParameter('{{ user }}', $player->getName())
            ->addViolation();
    }
}
