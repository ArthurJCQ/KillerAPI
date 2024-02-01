<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Serializer;

use App\Api\Exception\KillerHttpException;
use App\Api\Exception\KillerValidationException;
use App\Infrastructure\Serializer\KillerProblemNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class KillerProblemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    private ProblemNormalizer|ObjectProphecy $problemNormalizer;

    private KillerProblemNormalizer $killerProblemNormalizer;

    protected function setUp(): void
    {
        $this->problemNormalizer = $this->prophesize(ProblemNormalizer::class);

        $this->killerProblemNormalizer = new KillerProblemNormalizer($this->problemNormalizer->reveal());
    }

    public function testNormalizeKillerException(): void
    {
        $killerException = new KillerHttpException(400, 'KILLER_BAD_REQUEST');

        $context = [
            'exception' => $killerException,
        ];

        $this->problemNormalizer->normalize($killerException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->assertEquals(
            ['detail' => 'BAD_REQUEST'],
            $this->killerProblemNormalizer->normalize($killerException, null, $context),
        );
    }

    public function testNormalizeKillerValidationException(): void
    {
        $killerException = new KillerValidationException('KILLER_VALIDATION_ERROR', new ConstraintViolationList());

        $context = [
            'exception' => $killerException,
        ];

        $this->problemNormalizer->normalize($killerException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->assertEquals(
            ['detail' => 'VALIDATION_ERROR'],
            $this->killerProblemNormalizer->normalize($killerException, null, $context),
        );
    }

    public function testNormalizeRegularValidationException(): void
    {
        $killerException = new ValidationFailedException(400, new ConstraintViolationList());

        $context = [
            'exception' => $killerException,
        ];

        $this->problemNormalizer->normalize($killerException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->assertEquals(
            [],
            $this->killerProblemNormalizer->normalize($killerException, null, $context),
        );
    }

    public function testNormalizeRegularException(): void
    {
        $regularException = new BadRequestHttpException('KILLER_BAD_REQUEST');

        $context = [
            'exception' => $regularException,
        ];

        $this->problemNormalizer->normalize($regularException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->assertEquals(
            ['detail' => 'BAD_REQUEST'],
            $this->killerProblemNormalizer->normalize($regularException, null, $context),
        );
    }

    public function testDoNotNormalizeRegularException(): void
    {
        $regularException = new BadRequestHttpException('BAD_REQUEST');

        $context = [
            'exception' => $regularException,
        ];

        $this->problemNormalizer->normalize($regularException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn([]);

        $this->assertEquals(
            [],
            $this->killerProblemNormalizer->normalize($regularException, null, $context),
        );
    }
}
