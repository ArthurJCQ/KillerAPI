<?php

declare(strict_types=1);

namespace App\Domain\Player\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlayerCanRename extends Constraint
{
    public string $message = 'ALREADY_EXIST';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
