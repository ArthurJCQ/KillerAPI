<?php

declare(strict_types=1);

use App\Domain\Room\Entity\Room;
use Symfony\Config\FrameworkConfig;

return static function (FrameworkConfig $frameworkConfig): void {
    $roomLifecycle = $frameworkConfig->workflows()->workflows('room_lifecycle');

    $roomLifecycle
        ->type('state_machine')
        ->supports([Room::class])
        ->initialMarking('PENDING')
        ->markingStore()
        ->type('method')
        ->property('status');

    $roomLifecycle->place()->name('PENDING');
    $roomLifecycle->place()->name('IN_GAME');
    $roomLifecycle->place()->name('ENDED');

    $roomLifecycle->transition()
        ->name('start_game')
        ->guard('is_granted(\'edit_room\', subject)')
        ->from('PENDING')
        ->to('IN_GAME');

    $roomLifecycle->transition()
        ->name('end_game')
        ->from('IN_GAME')
        ->to('ENDED');
};
