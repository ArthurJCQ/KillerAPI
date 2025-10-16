<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class GameStartedNotification extends KillerNotification
{
    public string $title = 'La partie de Killer a commencé';
    public string $content = 'Nouvelle partie lancée ! Votre mission et votre cible vous attendent. Que la chasse commence ! 🔪';
}
