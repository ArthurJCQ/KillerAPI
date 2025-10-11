<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

use App\Domain\Player\Entity\Player;

abstract class KillerNotification
{
    public string $title = '';
    public string $content = '';
    /** @var array<Player> */
    public array $recipients = [];

    final protected function __construct(Player ...$recipients)
    {
        $this->recipients = $recipients;
    }

    public static function to(Player ...$recipients): self
    {
        return new static(...$recipients);
    }
}
