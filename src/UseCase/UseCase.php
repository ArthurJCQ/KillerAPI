<?php

declare(strict_types=1);

namespace App\UseCase;

interface UseCase
{
    public function supports(mixed $object): bool;

    public function execute(mixed $object): bool;
}
