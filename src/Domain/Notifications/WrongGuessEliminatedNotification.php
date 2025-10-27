<?php

declare(strict_types=1);

namespace App\Domain\Notifications;

final class WrongGuessEliminatedNotification extends KillerNotification
{
    public string $title = 'Votre cible a été éliminée';
    public string $content = 'Votre cible s\'est trompée en essayant de deviner son tueur et a été éliminée automatiquement !';
}
