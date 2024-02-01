<?php

declare(strict_types=1);

namespace App\Infrastructure\Validator;

use App\Api\Exception\KillerValidationException;
use App\Domain\KillerValidatorInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class KillerValidator implements KillerValidatorInterface
{
    public function __construct(private ValidatorInterface $validator)
    {
    }

    public function validate(object $entity): void
    {
        $violations = $this->validator->validate($entity);

        if (\count($violations) === 0) {
            return;
        }

        throw new KillerValidationException('KILLER_VALIDATION_ERROR', $violations);
    }
}
