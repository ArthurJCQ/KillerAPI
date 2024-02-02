<?php

declare(strict_types=1);

namespace App\Domain\Room\Exception;

use App\Domain\KillerExceptionInterface;

class NotEnoughMissionsInRoomException extends \DomainException implements KillerExceptionInterface
{
}
