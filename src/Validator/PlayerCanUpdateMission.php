<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlayerCanUpdateMission extends Constraint
{
    public string $message = 'The player {{ player }} can not update this mission';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
