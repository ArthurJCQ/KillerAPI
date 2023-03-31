<?php

declare(strict_types=1);

namespace App\Domain\Room\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CanPatchRoom extends Constraint
{
    public string $message = 'CAN_NOT_UPDATE_ROOM';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
