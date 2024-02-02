<?php

declare(strict_types=1);

namespace App\Tests\Infrastructure\Serializer;

use App\Api\Exception\KillerHttpException;
use App\Infrastructure\Serializer\KillerProblemNormalizer;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Serializer\Normalizer\ProblemNormalizer;
use Symfony\Component\Serializer\SerializerInterface;
use Symfony\Component\Validator\ConstraintViolationList;
use Symfony\Component\Validator\Exception\ValidationFailedException;

class KillerProblemNormalizerTest extends TestCase
{
    use ProphecyTrait;

    /** @var ProblemNormalizer|ObjectProphecy */
    private ObjectProphecy $problemNormalizer;

    /** @var SerializerInterface|ObjectProphecy */
    private ObjectProphecy $serializer;

    private KillerProblemNormalizer $killerProblemNormalizer;

    protected function setUp(): void
    {
        $this->problemNormalizer = $this->prophesize(ProblemNormalizer::class);
        $this->serializer = $this->prophesize(SerializerInterface::class);

        $this->killerProblemNormalizer = new KillerProblemNormalizer($this->problemNormalizer->reveal());
        $this->killerProblemNormalizer->setSerializer($this->serializer->reveal());
    }

    public function testNormalizeKillerException(): void
    {
        $this->problemNormalizer->setSerializer($this->serializer->reveal())->shouldBeCalledOnce();
        $killerException = new KillerHttpException(400, 'BAD_REQUEST');

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

    public function testNormalizeRegularValidationException(): void
    {
        $this->problemNormalizer->setSerializer($this->serializer->reveal())
            ->shouldBeCalledOnce();

        $regularException = new ValidationFailedException(400, new ConstraintViolationList([]));

        $context = [
            'exception' => $regularException,
        ];

        $this->problemNormalizer->normalize($regularException, null, $context)
            ->shouldBeCalledOnce()
            ->willReturn(['violations' => []]);

        $this->assertEquals(
            ['violations' => []],
            $this->killerProblemNormalizer->normalize($regularException, null, $context),
        );
    }

    public function testNormalizeRegularException(): void
    {
        $this->problemNormalizer->setSerializer($this->serializer->reveal())
            ->shouldBeCalledOnce();
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
        $this->problemNormalizer->setSerializer($this->serializer->reveal())
            ->shouldBeCalledOnce();
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
