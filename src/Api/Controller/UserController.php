<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\RoomRepository;
use App\Domain\User\Entity\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[Route('/user', format: 'json')]
class UserController extends AbstractController
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly PlayerRepository $playerRepository,
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
    ) {
    }

    #[Route(name: 'patch_user', methods: [Request::METHOD_PATCH])]
    public function patchUser(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            throw new NotFoundHttpException('KILLER_USER_NOT_FOUND');
        }

        $data = $request->toArray();

        // Handle room change
        if (array_key_exists('room', $data)) {
            $newRoomId = $data['room'];

            if ($newRoomId !== null) {
                $newRoom = $this->roomRepository->findOneBy(['id' => $newRoomId]);

                if ($newRoom === null) {
                    throw new NotFoundHttpException('ROOM_NOT_FOUND');
                }

                $user->setRoom($newRoom);
            }

            if ($newRoomId === null) {
                $user->setRoom(null);
            }

            // Remove room from data to avoid serializer issues
            unset($data['room']);
        }

        $this->serializer->deserialize(
            (string) $request->getContent(),
            User::class,
            [
                AbstractNormalizer::GROUPS => 'patch-user',
                AbstractNormalizer::OBJECT_TO_POPULATE => $user,
                AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            ],
        );

        $this->validator->validate($user);
        $this->persistenceAdapter->flush();

        return $this->json($user, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-user']);
    }
}
