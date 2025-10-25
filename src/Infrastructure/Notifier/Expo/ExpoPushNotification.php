<?php

declare(strict_types=1);

namespace App\Infrastructure\Notifier\Expo;

use App\Domain\Player\Entity\Player;
use Symfony\Component\Notifier\Bridge\Expo\ExpoOptions;
use Symfony\Component\Notifier\Message\PushMessage;
use Symfony\Component\Notifier\Notification\Notification;
use Symfony\Component\Notifier\Notification\PushNotificationInterface;
use Symfony\Component\Notifier\Recipient\RecipientInterface;

class ExpoPushNotification extends Notification implements PushNotificationInterface
{
    public const string TRANSPORT = 'expo';

    public function asPushMessage(RecipientInterface $recipient, ?string $transport = null): ?PushMessage
    {
        if ($transport === self::TRANSPORT && $recipient instanceof Player && $recipient->getExpoPushToken() !== '') {
            $pushMessage = PushMessage::fromNotification($this);

            $pushMessage->options(new ExpoOptions($recipient->getExpoPushToken()));

            return $pushMessage;
        }

        return null;
    }
}
