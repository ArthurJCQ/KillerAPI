<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifier\Expo;

use App\Domain\Notifications\KillerNotification;
use App\Domain\Notifications\KillerNotifier;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Psr\Log\NullLogger;
use Symfony\Component\Notifier\NotifierInterface;

class ExpoPushNotifier implements KillerNotifier, LoggerAwareInterface
{
    use LoggerAwareTrait;

    public const string PUSH_CHANNEL = 'push/expo';

    public function __construct(private readonly NotifierInterface $notifier)
    {
        $this->logger = new NullLogger();
    }

    public function notify(KillerNotification $killerNotif): void
    {
        $notification = (new ExpoPushNotification())
            ->subject($killerNotif->title)
            ->content($killerNotif->content)
            ->channels([self::PUSH_CHANNEL]);

        try {
            $this->notifier->send($notification, ...$killerNotif->recipients);
        } catch (\Throwable $e) {
            $this->logger->error($e->getMessage());
        }
    }
}
