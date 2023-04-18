<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\KillerBadRequestHttpException;
use App\Api\Exception\KillerValidationException;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Factory\RoomFactory;
use App\Domain\Room\RoomRepository;
use App\Domain\Room\RoomWorkflowTransitionInterface;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Infrastructure\Security\Voters\RoomVoter;
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
        private readonly RoomWorkflowTransitionInterface $roomStatusTransitionUseCase,
        private readonly HubInterface $hub,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
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
                    // CAN_NOT_MOVE_TO_IN_GAME or CAN_NO_MOVE_TO_ENDED
                    throw new KillerBadRequestHttpException(sprintf('CAN_NOT_MOVE_TO_%s', $data['status']));
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
        } catch (KillerValidationException $e) {
            throw new KillerBadRequestHttpException($e->getMessage());
        }

        $this->persistenceAdapter->flush();

        $this->hub->publish(new Update(
            sprintf('room/%s', $room),
            $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        ));

        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'delete_room', methods: [Request::METHOD_DELETE])]
    #[IsGranted(RoomVoter::EDIT_ROOM, subject: 'room')]
    public function deleteRoom(Room $room): JsonResponse
    {
        $roomCode = $room->getId();
        $this->roomRepository->remove($room);

        $this->persistenceAdapter->flush();

        $this->hub->publish(new Update(
            sprintf('room/%s', $roomCode),
            $this->serializer->serialize((object) []),
        ));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
