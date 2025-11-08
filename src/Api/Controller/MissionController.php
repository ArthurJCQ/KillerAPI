<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\KillerBadRequestHttpException;
use App\Api\Request\MissionRequest;
use App\Application\UseCase\Mission\CreateMissionUseCase;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Infrastructure\Security\Voters\MissionVoter;
use App\Infrastructure\SSE\SseInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/mission', format: 'json')]
class MissionController extends AbstractController
{
    public function __construct(
        private readonly MissionRepository $missionRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly SseInterface $hub,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
        private readonly CreateMissionUseCase $createMissionUseCase,
    ) {
    }

    #[Route(name: 'create_mission', methods: [Request::METHOD_POST])]
    #[IsGranted(MissionVoter::CREATE_MISSION, message: 'KILLER_CREATE_MISSION_UNAUTHORIZED')]
    public function createMission(
        #[MapRequestPayload] MissionRequest $request,
    ): JsonResponse {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            throw $this->createNotFoundException('KILLER_USER_NOT_FOUND');
        }

        $player = $this->playerRepository->getCurrentUserPlayer($user);

        if ($player === null) {
            throw $this->createNotFoundException('PLAYER_NOT_FOUND_IN_CURRENT_ROOM');
        }

        $room = $player->getRoom();

        if (!$room || $room->getStatus() !== Room::PENDING) {
            throw new KillerBadRequestHttpException('CAN_NOT_ADD_MISSIONS');
        }

        $mission = $this->createMissionUseCase->execute($request->content, $player);
        $mission->setRoom($room);

        $this->missionRepository->store($mission);
        $this->persistenceAdapter->flush();

        $this->hub->publish(
            sprintf('room/%s', $room->getId()),
            $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        );

        return $this->json($mission, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/{id}', name: 'get-mission', methods: [Request::METHOD_GET])]
    #[IsGranted(MissionVoter::VIEW_MISSION, subject: 'mission', message: 'KILLER_VIEW_MISSION_UNAUTHORIZED')]
    public function getMission(Mission $mission): JsonResponse
    {
        return $this->json($mission, Response::HTTP_OK, [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/{id}', name: 'patch_mission', methods: [Request::METHOD_PATCH])]
    #[IsGranted(MissionVoter::EDIT_MISSION, subject: 'mission', message: 'KILLER_EDIT_MISSION_UNAUTHORIZED')]
    public function patchMission(Request $request, Mission $mission): JsonResponse
    {
        $this->serializer->deserialize(
            (string) $request->getContent(),
            Mission::class,
            [
                AbstractNormalizer::GROUPS => 'post-mission',
                AbstractNormalizer::OBJECT_TO_POPULATE => $mission,
            ],
        );

        $this->validator->validate($mission);

        $this->persistenceAdapter->flush();

        return $this->json($mission, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/room', name: 'count_room_missions', methods: [Request::METHOD_GET])]
    public function countAllMissionsInRoom(Room $room): JsonResponse
    {
        $nbMissions = $this->missionRepository->countMissionByRoom($room);

        return $this->json($nbMissions, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/{id}', name: 'delete_mission', methods: [Request::METHOD_DELETE])]
    #[IsGranted(MissionVoter::EDIT_MISSION, subject: 'mission', message: 'KILLER_DELETE_MISSION_UNAUTHORIZED')]
    public function deleteMission(Mission $mission): JsonResponse
    {
        $this->missionRepository->remove($mission);

        $this->persistenceAdapter->flush();

        /** @var User|null $user */
        $user = $this->getUser();

        if ($user !== null) {
            $player = $this->playerRepository->getCurrentUserPlayer($user);

            if ($player !== null && $player->getRoom() !== null) {
                $this->hub->publish(
                    sprintf('room/%s', $player->getRoom()->getId()),
                    $this->serializer->serialize(
                        (object) $player->getRoom(),
                        [AbstractNormalizer::GROUPS => 'publish-mercure'],
                    ),
                );
            }
        }

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
