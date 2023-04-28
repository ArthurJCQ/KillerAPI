<?php

declare(strict_types=1);

namespace App\Infrastructure\SSE;

interface SseInterface
{
    /** @param string|array<string, string> $topics */
    public function publish(string|array $topics, string $data): void;
}
