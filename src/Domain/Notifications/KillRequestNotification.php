<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class KillRequestNotification extends KillerNotification
{
    public string $title = 'Votre vie est en jeu';
    public string $content = "Quelqu'un affirme avoir exécuté votre mission. Le coup fatal a-t-il été porté ? Répondez vite pour vérifier le meurtre !";
}
