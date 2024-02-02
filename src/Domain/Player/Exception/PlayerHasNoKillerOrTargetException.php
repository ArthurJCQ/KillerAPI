<?php

declare(strict_types=1);

namespace App\Domain\Player\Exception;

use App\Domain\KillerExceptionInterface;

class PlayerHasNoKillerOrTargetException extends \DomainException implements KillerExceptionInterface
{
}
