<?php

declare(strict_types=1);

namespace App\Validator;

use App\Api\Exception\KillerValidationException;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class KillerValidator
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

        throw new KillerValidationException((string) $violations->get(0)->getMessage());
    }
}
