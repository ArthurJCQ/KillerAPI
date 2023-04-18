<?php

declare(strict_types=1);

namespace App\Domain\Player\Dto;

use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Entity\Room;

class UpdatePlayerDto
{
    public string $name;

    public PlayerStatus $status;

    public ?Room $room = null;

    public string $avatar;

    public bool $isUpdatingRoom = false;
}
