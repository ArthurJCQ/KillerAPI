<?php

declare(strict_types=1);

namespace App\Domain\Player;

use App\Domain\Player\Entity\Player;

interface PlayerUseCase
{
    public function execute(Player $player): void;
}
