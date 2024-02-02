<?php

declare(strict_types=1);

namespace App\Api\Exception;

use App\Domain\KillerExceptionInterface;
use Symfony\Component\HttpKernel\Exception\HttpException;

class KillerHttpException extends HttpException implements KillerExceptionInterface
{
}
