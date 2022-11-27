<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\ValidationException;
use App\Domain\Mission\Entity\Mission;
use App\Domain\Mission\MissionRepository;
use App\Domain\Mission\Security\MissionVoter;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
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

#[Route('/mission')]
class MissionController extends AbstractController
{
    public function __construct(
        private readonly MissionRepository $missionRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly HubInterface $hub,
        private readonly KillerSerializer $serializer,
        private readonly KillerValidator $validator,
    ) {
    }

    #[Route(name: 'create_mission', methods: [Request::METHOD_POST])]
    public function createMission(Request $request): JsonResponse
    {
        /** @var Player $player */
        $player = $this->getUser();
        $room = $player->getRoom();

        if (!$room || $room->getStatus() !== Room::PENDING) {
            throw new BadRequestHttpException('Enter a PENDING room before adding missions');
        }

        $mission = $this->serializer->deserialize(
            (string) $request->getContent(),
            Mission::class,
            [AbstractNormalizer::GROUPS => 'post-mission'],
        );
        $mission->setAuthor($player);
        $room->addMission($mission);

        try {
            $this->validator->validate($mission);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->missionRepository->store($mission);
        $this->persistenceAdapter->flush();

//        $this->hub->publish(new Update(sprintf('room/%s', $mission->getAuthor()?->getRoom())));

        return $this->json($mission, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/{id}', name: 'get-mission', methods: [Request::METHOD_GET])]
    #[IsGranted(MissionVoter::VIEW_MISSION, subject: 'mission')]
    public function getMission(Mission $mission): JsonResponse
    {
        return $this->json($mission, Response::HTTP_OK, [AbstractNormalizer::GROUPS => 'get-mission']);
    }

    #[Route('/{id}', name: 'patch_mission', methods: [Request::METHOD_PATCH])]
    #[IsGranted(MissionVoter::EDIT_MISSION, subject: 'mission')]
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

        try {
            $this->validator->validate($mission);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

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
    #[IsGranted(MissionVoter::EDIT_MISSION, subject: 'mission')]
    public function deleteMission(Mission $mission): JsonResponse
    {
        $this->missionRepository->remove($mission);

        $this->persistenceAdapter->flush();

        /** @var Player $player */
        $player = $this->getUser();

        $this->hub->publish(new Update(sprintf('room/%s', $player->getRoom())));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
