<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\ValidationException;
use App\Domain\Player\Entity\Player;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\Factory\RoomFactory;
use App\Domain\Room\RoomRepository;
use App\Domain\Room\UseCase\RoomStatusTransitionUseCase;
use App\Infrastructure\Persistence\Doctrine\DoctrinePersistenceAdapter;
use App\Serializer\KillerSerializer;
use App\Validator\KillerValidator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Requirement\Requirement;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;

#[Route('/room')]
class RoomController extends AbstractController
{
    public function __construct(
        private readonly RoomRepository $roomRepository,
        private readonly DoctrinePersistenceAdapter $persistenceAdapter,
        private readonly RoomStatusTransitionUseCase $roomStatusTransitionUseCase,
        private readonly HubInterface $hub,
        private readonly KillerSerializer $serializer,
        private readonly KillerValidator $validator,
        private readonly RoomFactory $roomFactory,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    #[Route(name: 'create_room', methods: [Request::METHOD_POST])]
    public function createRoom(): JsonResponse
    {
        /** @var Player $player */
        $player = $this->getUser();

        $room = $this->roomFactory->create();

        $player->setRoles([Player::ROLE_ADMIN]);

        $this->roomRepository->store($room);
        $this->persistenceAdapter->flush();

        // TODO Find a solution to do it in a post flush event
        $this->tokenStorage->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));

        return $this->json($room, Response::HTTP_CREATED, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'get_room', methods: [Request::METHOD_GET])]
    public function getRoom(Room $room): JsonResponse
    {
        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'patch_room', requirements: ['id' => Requirement::DIGITS], methods: [Request::METHOD_PATCH])]
    public function patchRoom(Request $request, Room $room): JsonResponse
    {
        $player = $this->getUser();
        $data = $request->toArray();

        if (!$player instanceof Player || $player->getRoom() !== $room) {
            throw new UnauthorizedHttpException('User has no rights to update this room');
        }

        if (isset($data['status'])) {
            try {
                $this->roomStatusTransitionUseCase->execute($room, $data['status']);
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
            ],
        );

        try {
            $this->validator->validate($room);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->persistenceAdapter->flush();

//        $this->hub->publish(new Update(
//            sprintf('room/%s', $room),
//            $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'get-room']),
//        ));

        return $this->json($room, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-room']);
    }

    #[Route('/{id}', name: 'delete_room', methods: [Request::METHOD_DELETE])]
    public function deleteRoom(Room $room): JsonResponse
    {
        $player = $this->getUser();

        if (!$player instanceof Player || $player->getRoom() !== $room) {
            throw new UnauthorizedHttpException('User has no rights to delete this room');
        }

        $this->roomRepository->remove($room);

        $this->persistenceAdapter->flush();

        $this->hub->publish(new Update(sprintf('room/%s', $room)));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
