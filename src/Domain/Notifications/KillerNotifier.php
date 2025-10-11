<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

interface KillerNotifier
{
    public function notify(KillerNotification $killerNotif): void;
}
