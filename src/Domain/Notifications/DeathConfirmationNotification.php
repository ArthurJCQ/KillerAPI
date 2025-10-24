<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class DeathConfirmationNotification extends KillerNotification
{
    public string $title = 'Mission accomplie';
    public string $content = 'Votre cible a confirmé sa mort. Votre mission est réussie mais ce n\'est pas la fin de la chasse !';
}
