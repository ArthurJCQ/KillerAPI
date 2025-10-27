<?php

declare(strict_types=1);

namespace App\Api\Request;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class MissionRequest
{
    public function __construct(
        #[Assert\NotBlank(message: 'MISSION_CONTENT_REQUIRED')]
        #[Assert\Length(
            min: 5,
            max: 1000,
            minMessage: 'MISSION_TOO_SHORT_CONTENT',
            maxMessage: 'MISSION_TOO_LONG_CONTENT',
        )]
        public string $content,
    ) {
    }
}
