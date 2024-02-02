<?php

declare(strict_types=1);

namespace App\Domain\Room\Exception;

use App\Domain\KillerExceptionInterface;

class RoomNotInGameException extends \DomainException implements KillerExceptionInterface
{
}
