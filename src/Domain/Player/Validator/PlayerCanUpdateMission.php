<?php

declare(strict_types=1);

namespace App\Domain\Player\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlayerCanUpdateMission extends Constraint
{
    public string $message = 'PLAYER_CAN_NOT_UPDATE_MISSION';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
