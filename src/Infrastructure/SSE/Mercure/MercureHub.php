<?php

declare(strict_types=1);

namespace App\Infrastructure\SSE\Mercure;

use App\Infrastructure\SSE\SseInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Component\Mercure\Exception\ExceptionInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

final class MercureHub implements SseInterface, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(private readonly HubInterface $hub)
    {
    }

    /** @param string|array<string, string> $topics */
    public function publish(string|array $topics, string $data): void
    {
        try {
            $this->hub->publish(new Update($topics, $data));
        } catch (ExceptionInterface $e) {
            $this->logger->error(sprintf('Could not send Mercure update : %s', $e->getMessage()));
        }
    }
}
