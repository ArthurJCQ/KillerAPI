<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\UseCase\Player\CreatePlayerUseCase;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Player\Event\PlayerChangedRoomEvent;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\RoomRepository;
use App\Domain\User\Entity\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Http\Cookie\CookieProvider;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/user', format: 'json')]
class UserController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly RoomRepository $roomRepository,
        private readonly UserRepository $userRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
        private readonly CreatePlayerUseCase $createPlayerUseCase,
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly EventDispatcherInterface $eventDispatcher,
    ) {
    }

    #[Route(name: 'create_user', methods: [Request::METHOD_POST])]
    public function createUser(
        #[MapRequestPayload(serializationContext: [AbstractNormalizer::GROUPS => 'post-user'])] User $user,
    ): JsonResponse {
        // Store the user
        $this->userRepository->store($user);
        $this->persistenceAdapter->flush();

        // Generate JWT tokens for the User
        $jwtToken = $this->tokenManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 15552000);
        $this->refreshTokenManager->save($refreshToken);

        $user->setToken($jwtToken);
        $user->setRefreshToken($refreshToken->getRefreshToken() ?? '');

        $this->logger->info('Token and refresh token created for user {user_id}', ['user_id' => $user->getId()]);

        return $this->json(
            $user,
            Response::HTTP_CREATED,
            ['Location' => sprintf('/user/%s', $user->getId())],
            [AbstractNormalizer::GROUPS => 'create-user'],
        );
    }

    #[Route('/me', name: 'get_user_me', methods: [Request::METHOD_GET])]
    public function me(): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            throw $this->createNotFoundException('KILLER_USER_NOT_FOUND');
        }

        // Serialize user with all info and players list
        $userJson = $this->serializer->serialize(
            $user,
            [
                AbstractNormalizer::GROUPS => 'me',
                AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ],
        );

        $userData = json_decode($userJson, true);

        // Get the current player based on the user's room context
        $currentPlayer = $this->playerRepository->getCurrentUserPlayer($user);

        // Add currentPlayer property if found
        if ($currentPlayer !== null) {
            $currentPlayerJson = $this->serializer->serialize(
                $currentPlayer,
                [
                    AbstractNormalizer::GROUPS => 'me',
                    AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
                ],
            );

            if (is_array($userData)) {
                $userData['currentPlayer'] = json_decode($currentPlayerJson, true);
            }
        }

        $response = new JsonResponse($userData, Response::HTTP_OK);

        $response->headers->setCookie(CookieProvider::getJwtCookie(
            ['mercure', ['subscribe' => ['*']]],
            is_string($this->getParameter('mercure.jwt_secret')) ? $this->getParameter('mercure.jwt_secret') : '',
            'mercureAuthorization',
            null,
            'Lax',
            is_string($this->getParameter('mercure.path')) ? $this->getParameter('mercure.path') : '',
            is_string($this->getParameter('mercure.domain')) ? $this->getParameter('mercure.domain') : '',
        ));

        return $response;
    }

    #[Route(name: 'patch_user', methods: [Request::METHOD_PATCH])]
    public function patchUser(Request $request): JsonResponse
    {
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            throw $this->createNotFoundException('KILLER_USER_NOT_FOUND');
        }

        $data = $request->toArray();
        $currentPlayer = $this->playerRepository->getCurrentUserPlayer($user);

        // Handle room change
        if (array_key_exists('room', $data)) {
            $newRoomId = $data['room'];

            if ($newRoomId !== null) {
                $newRoom = $this->roomRepository->findOneBy(['id' => $newRoomId]);

                if ($newRoom === null) {
                    throw $this->createNotFoundException('ROOM_NOT_FOUND');
                }

                $existingPlayer = $this->playerRepository->findPlayerByUserAndRoom($user, $newRoom);
                $user->setRoom($newRoom);

                // Create a new player for this user in the room if one doesn't exist
                if ($existingPlayer === null) {
                    $this->createPlayerUseCase->execute($user, $newRoom);
                }
            }

            if ($newRoomId === null) {
                $user->setRoom(null);

                if ($currentPlayer) {
                    $this->eventDispatcher->dispatch(new PlayerChangedRoomEvent(
                        $currentPlayer,
                        $currentPlayer->getRoom(),
                    ));
                }
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
