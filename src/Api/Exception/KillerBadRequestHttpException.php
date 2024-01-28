<?php

declare(strict_types=1);

namespace App\Api\Exception;

class KillerBadRequestHttpException extends KillerHttpException
{
    public function __construct(string $message = '', ?\Throwable $previous = null, int $code = 0, array $headers = [])
    {
        parent::__construct(400, $message, $previous, $headers, $code);
    }
}
