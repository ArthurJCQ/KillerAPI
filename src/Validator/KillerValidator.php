<?php

declare(strict_types=1);

namespace App\Validator;

use App\Exception\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class KillerValidator
{
    public function __construct(
        private readonly SerializerInterface $serializer,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function validate(object $entity): void
    {
        $violations = $this->validator->validate($entity);

        if (\count($violations) === 0) {
            return;
        }

        throw new ValidationException($this->serializer->serialize($violations, 'json'));
    }
}
