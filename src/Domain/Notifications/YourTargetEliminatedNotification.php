<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class YourTargetEliminatedNotification extends KillerNotification
{
    public string $title = 'Votre cible a été éliminée';
    public string $content = 'Votre cible a été éliminée par quelqu\'un d\'autre.'
        . 'Une nouvelle cible vous a été assignée !';
}
