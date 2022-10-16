<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class PlayerCanRename extends Constraint
{
    public string $message = 'The user {{ user }} can not be renamed as another user have the same name';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
