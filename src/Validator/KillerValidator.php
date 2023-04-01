<?php

declare(strict_types=1);

namespace App\Validator;

use App\Api\Exception\ValidationException;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

readonly class KillerValidator
{
    public function __construct(
        private SerializerInterface $serializer,
        private ValidatorInterface $validator,
    ) {
    }

    public function validate(object $entity): void
    {
        $violations = $this->validator->validate($entity);

        if (\count($violations) === 0) {
            return;
        }

        throw new ValidationException($this->serializer->serialize($violations->get(0)->getMessage(), 'json'));
    }
}
