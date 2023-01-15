<?php

declare(strict_types=1);

namespace App\Tests\Unit\Domain\Room\Request\ParamConverter;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\Request\ParamConverter\RoomConverter;
use App\Domain\Room\RoomRepository;
use Prophecy\PhpUnit\ProphecyTrait;
use Prophecy\Prophecy\ObjectProphecy;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Symfony\Component\HttpFoundation\Request;

class RoomConverterTest extends \Codeception\Test\Unit
{
    use ProphecyTrait;

    private ObjectProphecy $roomRepository;

    private RoomConverter $roomConverter;

    protected function setUp(): void
    {
        $this->roomRepository = $this->prophesize(RoomRepository::class);

        $this->roomConverter = new RoomConverter($this->roomRepository->reveal());
    }

    public function testSupport(): void
    {
        $configuration = $this->prophesize(ParamConverter::class);

        $configuration->getName()->shouldBeCalledOnce()->willReturn('room');
        $configuration->getClass()->shouldBeCalledOnce()->willReturn(Room::class);

        $this->assertTrue($this->roomConverter->supports($configuration->reveal()));
    }

    public function testApply(): void
    {
        $identifier = 'X1X1X';
        $request = new Request();
        $request->attributes->set('id', $identifier);

        $configuration = $this->prophesize(ParamConverter::class);

        $room = $this->prophesize(Room::class);
        $this->roomRepository->getRoomByIdOrCode($identifier)->shouldBeCalledOnce()->willReturn($room->reveal());

        $this->assertTrue($this->roomConverter->apply($request, $configuration->reveal()));
        $this->assertSame($room->reveal(), $request->attributes->get('room'));
    }
}
