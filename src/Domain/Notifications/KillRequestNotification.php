<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class KillRequestNotification extends KillerNotification
{
    public string $title = 'Tu as été tué !';
    public string $content = 'Confirme ta mort dès maintenant...';
}
