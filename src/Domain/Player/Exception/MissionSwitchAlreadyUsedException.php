<?php

declare(strict_types=1);

namespace App\Domain\Player\Exception;

use App\Domain\KillerExceptionInterface;

class MissionSwitchAlreadyUsedException extends \DomainException implements KillerExceptionInterface
{
}
