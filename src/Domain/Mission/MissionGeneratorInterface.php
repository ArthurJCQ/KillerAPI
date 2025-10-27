<?php

declare(strict_types=1);

namespace App\Domain\Mission;

interface MissionGeneratorInterface
{
    /**
     * Generate a random mission content.
     *
     * @return string The mission content
     */
    public function generate(): string;
}
