<?php

declare(strict_types=1);

namespace App\Validator;

use Symfony\Component\Validator\Constraint;

#[\Attribute]
class CanPatchRoom extends Constraint
{
    public string $message = 'Can not patch room {{ room }}';

    public function getTargets(): string
    {
        return self::CLASS_CONSTRAINT;
    }
}
