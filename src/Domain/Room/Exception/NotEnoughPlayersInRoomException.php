<?php

declare(strict_types=1);

namespace App\Domain\Room\Exception;

use App\Domain\KillerExceptionInterface;

class NotEnoughPlayersInRoomException extends \DomainException implements KillerExceptionInterface
{
}
