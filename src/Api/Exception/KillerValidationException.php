<?php

declare(strict_types=1);

namespace App\Api\Exception;

use Symfony\Component\Validator\Exception\ValidationFailedException;

class KillerValidationException extends ValidationFailedException
{
}
