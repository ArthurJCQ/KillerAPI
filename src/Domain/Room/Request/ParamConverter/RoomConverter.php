<?php

declare(strict_types=1);

namespace App\Domain\Room\Request\ParamConverter;

use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\ParamConverter;
use Sensio\Bundle\FrameworkExtraBundle\Request\ParamConverter\ParamConverterInterface;
use Symfony\Component\HttpFoundation\Request;

readonly class RoomConverter implements ParamConverterInterface
{
    public function __construct(private RoomRepository $roomRepository)
    {
    }

    public function apply(Request $request, ParamConverter $configuration): bool
    {
        $roomIdentifier = $request->attributes->get('id');

        $room = $this->roomRepository->getRoomByIdOrCode($roomIdentifier);

        $request->attributes->set('room', $room);

        return true;
    }

    public function supports(ParamConverter $configuration): bool
    {
        return $configuration->getClass() === Room::class && $configuration->getName() === 'room';
    }
}
