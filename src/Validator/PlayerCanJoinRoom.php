<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlayerCanJoinRoom extends Constraint
{
    public string $message = 'User {{ user }} can not join room {{ room }}, room may already be started.';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
