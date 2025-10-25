<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Dto\GenerateRoomWithMissionsDto;
use App\Api\Exception\KillerBadRequestHttpException;
use App\Application\UseCase\Room\CreateRoomUseCase;
use App\Application\UseCase\Room\GenerateRoomWithMissionUseCase;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Domain\Room\RoomWorkflowTransitionInterface;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Infrastructure\Security\Voters\RoomVoter;
use App\Infrastructure\SSE\SseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[Route('/room', format: 'json')]
class RoomController extends AbstractController
{
    public const string IS_GAME_MASTERED_ROOM = 'isGameMastered';

    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly RoomWorkflowTransitionInterface $roomStatusTransitionUseCase,
        private readonly SseInterface $hub,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
        private readonly CreateRoomUseCase $createRoomUseCase,
        private readonly GenerateRoomWithMissionUseCase $generateRoomWithMissionUseCase,
    ) {
    }

    #[Route(name: 'create_room', methods: [Request::METHOD_POST])]
    #[IsGranted(RoomVoter::CREATE_ROOM, message: 'KILLER_CREATE_ROOM_UNAUTHORIZED')]
    public function createRoom(Request $request): JsonResponse
    {
        /** @var Player $player */
        $player = $this->getUser();
        $roomName = sprintf("%s's room", $player->getName());
        $isGameMastered = false;

        if ($request->getContent() !== '') {
            $data = $request->toArray();
            $isGameMastered = $data[self::IS_GAME_MASTERED_ROOM] ?? false;
        }

        $room = $this->createRoomUseCase->execute($player, $roomName, $isGameMastered);

        return $this->json($room, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/generate-with-missions', name: 'generate_room_with_missions', methods: [Request::METHOD_POST])]
    #[IsGranted(RoomVoter::CREATE_ROOM, message: 'KILLER_CREATE_ROOM_UNAUTHORIZED')]
    public function generateRoomWithMissions(
        #[MapRequestPayload] GenerateRoomWithMissionsDto $dto,
    ): JsonResponse {
        /** @var Player $player */
        $player = $this->getUser();
        $roomName = $dto->roomName ?? sprintf("%s's room", $player->getName());
        $missionsCount = $dto->missionsCount ?? 10;

        $room = $this->generateRoomWithMissionUseCase->execute($roomName, $player, $missionsCount, $dto->theme);

        return $this->json($room, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'get_room', methods: [Request::METHOD_GET])]
    #[IsGranted(RoomVoter::VIEW_ROOM, subject: 'room', message: 'KILLER_VIEW_ROOM_UNAUTHORIZED')]
    public function getRoom(Room $room): JsonResponse
    {
        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'patch_room', methods: [Request::METHOD_PATCH])]
    #[IsGranted(RoomVoter::EDIT_ROOM, subject: 'room', message: 'KILLER_EDIT_ROOM_UNAUTHORIZED')]
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

        $this->validator->validate($room);

        $this->persistenceAdapter->flush();

        $this->hub->publish(
            sprintf('room/%s', $room),
            $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        );

        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'delete_room', methods: [Request::METHOD_DELETE])]
    #[IsGranted(RoomVoter::EDIT_ROOM, subject: 'room', message: 'KILLER_DELETE_ROOM_UNAUTHORIZED')]
    public function deleteRoom(Room $room): JsonResponse
    {
        $roomCode = $room->getId();
        $this->roomRepository->remove($room);

        $this->persistenceAdapter->flush();

        $this->hub->publish(
            sprintf('room/%s', $roomCode),
            $this->serializer->serialize((object) []),
        );

        return $this->json(null, Response::HTTP_OK);
    }
}
