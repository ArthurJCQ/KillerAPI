<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class KillerEliminatedByGuessNotification extends KillerNotification
{
    public string $title = 'Vous avez été éliminé !';
    public string $content = 'Votre cible a deviné votre identité ! Vous avez été éliminé de la partie.';
}
