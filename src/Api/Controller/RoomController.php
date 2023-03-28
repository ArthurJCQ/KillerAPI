<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\ValidationException;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Factory\RoomFactory;
use App\Domain\Room\RoomRepository;
use App\Domain\Room\Security\RoomVoter;
use App\Domain\Room\Workflow\RoomStatusTransitionUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Serializer\KillerSerializer;
use App\Validator\KillerValidator;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[Route('/room', format: 'json')]
class RoomController extends AbstractController
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly RoomStatusTransitionUseCase $roomStatusTransitionUseCase,
        private readonly HubInterface $hub,
        private readonly KillerSerializer $serializer,
        private readonly KillerValidator $validator,
        private readonly RoomFactory $roomFactory,
    ) {
    }

    #[Route(name: 'create_room', methods: [Request::METHOD_POST])]
    #[IsGranted(RoomVoter::CREATE_ROOM)]
    public function createRoom(): JsonResponse
    {
        $room = $this->roomFactory->create();

        $this->roomRepository->store($room);
        $this->persistenceAdapter->flush();

        return $this->json($room, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'get_room', methods: [Request::METHOD_GET])]
    #[IsGranted(RoomVoter::VIEW_ROOM, subject: 'room')]
    public function getRoom(Room $room): JsonResponse
    {
        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'patch_room', methods: [Request::METHOD_PATCH])]
    #[IsGranted(RoomVoter::EDIT_ROOM, subject: 'room')]
    public function patchRoom(Request $request, Room $room): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data['status'])) {
            try {
                $transitionSuccess = $this->roomStatusTransitionUseCase->executeTransition($room, $data['status']);

                if (!$transitionSuccess) {
                    throw new BadRequestHttpException(sprintf('Can not update room status to %s', $data['status']));
                }
            } catch (\DomainException $e) {
                throw new BadRequestHttpException($e->getMessage());
            }
        }

        $this->serializer->deserialize(
            (string) $request->getContent(),
            Room::class,
            [
                AbstractNormalizer::GROUPS => 'patch-room',
                AbstractNormalizer::OBJECT_TO_POPULATE => $room,
                AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            ],
        );

        try {
            $this->validator->validate($room);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->persistenceAdapter->flush();

        $this->hub->publish(new Update(
            sprintf('room/%s', $room),
            $this->serializer->serialize((object) ['type' => 'ROOM_UPDATED']),
        ));

        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'delete_room', methods: [Request::METHOD_DELETE])]
    #[IsGranted(RoomVoter::EDIT_ROOM, subject: 'room')]
    public function deleteRoom(Room $room): JsonResponse
    {
        $this->roomRepository->remove($room);

        $this->persistenceAdapter->flush();

        $this->hub->publish(new Update(
            sprintf('room/%s', $room),
            $this->serializer->serialize((object) ['type' => 'ROOM_UPDATED']),
        ));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
