<?php

declare(strict_types=1);

namespace App\Domain;

interface KillerValidatorInterface
{
    public function validate(object $entity): void;
}
