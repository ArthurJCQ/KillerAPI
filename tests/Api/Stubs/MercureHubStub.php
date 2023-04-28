<?php

declare(strict_types=1);

namespace App\Tests\Api\Stubs;

use App\Infrastructure\SSE\SseInterface;

class MercureHubStub implements SseInterface
{
    /** @param string|array<string, string> $topics */
    public function publish(array|string $topics, string $data): void
    {
        // Send update stub
    }
}
