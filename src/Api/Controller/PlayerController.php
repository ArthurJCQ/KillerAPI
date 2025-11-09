<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\KillerBadRequestHttpException;
use App\Application\UseCase\Player\ContestKillUseCase;
use App\Application\UseCase\Player\GuessKillerUseCase;
use App\Application\UseCase\Player\KillRequestOnTargetUseCase;
use App\Application\UseCase\Player\SwitchMissionUseCase;
use App\Domain\KillerExceptionInterface;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Event\PlayerUpdatedEvent;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomWorkflowTransitionInterface;
use App\Domain\User\Entity\User;
use App\Domain\User\UserRepository;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Infrastructure\Security\Voters\PlayerVoter;
use App\Infrastructure\SSE\SseInterface;
use Gesdinet\JWTRefreshTokenBundle\Generator\RefreshTokenGeneratorInterface;
use Gesdinet\JWTRefreshTokenBundle\Model\RefreshTokenManagerInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Contracts\EventDispatcher\EventDispatcherInterface;

#[Route('/player', format: 'json')]
class PlayerController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly UserRepository $userRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly SseInterface $hub,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly RefreshTokenGeneratorInterface $refreshTokenGenerator,
        private readonly RefreshTokenManagerInterface $refreshTokenManager,
        private readonly RoomWorkflowTransitionInterface $roomStatusTransitionUseCase,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        private readonly KillRequestOnTargetUseCase $killRequestOnTargetUseCase,
        private readonly SwitchMissionUseCase $switchMissionUseCase,
        private readonly GuessKillerUseCase $guessKillerUseCase,
        private readonly ContestKillUseCase $contestKillUseCase,
    ) {
    }

    #[Route(name: 'create_player', methods: [Request::METHOD_POST])]
    public function createPlayer(
        #[MapRequestPayload(serializationContext: [AbstractNormalizer::GROUPS => 'post-player'])] Player $player,
    ): JsonResponse {
        // Check if user already exists (OAuth or existing session)
        /** @var User|null $user */
        $user = $this->getUser();

        if ($user === null) {
            // Create a new User for this Player (for backward compatibility with non-OAuth flows)
            $user = new User();
            $user->setName($player->getName());
            $this->userRepository->store($user);
        }

        // Link the Player to the User
        $player->setUser($user);
        $this->playerRepository->store($player);
        $this->persistenceAdapter->flush();

        // Generate JWT tokens for the User (not the Player)
        $jwtToken = $this->tokenManager->create($user);
        $refreshToken = $this->refreshTokenGenerator->createForUserWithTtl($user, 15552000);
        $this->refreshTokenManager->save($refreshToken);

        $player->setToken($jwtToken);
        $player->setRefreshToken($refreshToken->getRefreshToken() ?? '');

        $this->logger->info('Token and refresh token created for user {user_id}', ['user_id' => $user->getId()]);

        return $this->json(
            $player,
            Response::HTTP_CREATED,
            ['Location' => sprintf('/player/%s', $player->getId())],
            [AbstractNormalizer::GROUPS => 'create-player'],
        );
    }

    #[Route('/{id}', name: 'get_player', methods: [Request::METHOD_GET])]
    public function getPlayerById(Player $player): JsonResponse
    {
        $serializationGroups = $this->security->isGranted('ROLE_MASTER')
            ? ['get-player-master', 'get-player']
            : 'get-player';

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => $serializationGroups]);
    }

    #[Route('/{id}', name: 'patch_player', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_EDIT_PLAYER_UNAUTHORIZED')]
    public function patchPlayer(Request $request, Player $player): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data['role']) && $player !== $player->getRoom()?->getAdmin()) {
            throw new UnauthorizedHttpException('KILLER_CAN_NOT_UPDATE_PLAYER_ROLE');
        }

        // Players cannot change their room - use UserController to change user's room context
        if (array_key_exists('room', $data)) {
            throw new KillerBadRequestHttpException('PLAYER_CANNOT_CHANGE_ROOM');
        }

        if (isset($data['status']) && $data['status'] === PlayerStatus::KILLED->value) {
            $this->eventDispatcher->dispatch(new PlayerKilledEvent($player));
        }

        $this->serializer->deserialize(
            (string) $request->getContent(),
            Player::class,
            [
                AbstractNormalizer::GROUPS => 'patch-player',
                AbstractNormalizer::OBJECT_TO_POPULATE => $player,
                AbstractObjectNormalizer::DEEP_OBJECT_TO_POPULATE => true,
            ],
        );

        $this->validator->validate($player);

        $this->logger->info('KILLER : player target : {player_target}', ['player_target' => $player->getTarget()]);

        $this->persistenceAdapter->flush();

        $this->eventDispatcher->dispatch(new PlayerUpdatedEvent($player));

        $this->hub->publish(
            sprintf('room/%s', $player->getRoom()?->getId()),
            $this->serializer->serialize(
                (object) $player->getRoom(),
                [AbstractNormalizer::GROUPS => 'publish-mercure'],
            ),
        );

        $this->logger->info('Event mercure sent: post-PATCH for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-player']);
    }

    #[Route('/{id}', name: 'delete_player', methods: [Request::METHOD_DELETE])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_DELETE_PLAYER_UNAUTHORIZED')]
    public function deletePlayer(Player $player): JsonResponse
    {
        $room = $player->getRoom();

        // Try to end room after player deletion.
        if ($room instanceof Room) {
            $this->roomStatusTransitionUseCase->executeTransition($room, Room::ENDED);
        }

        $this->hub->publish(
            sprintf('room/%s', $room),
            $this->serializer->serialize((object) $room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        );
        $this->logger->info('Event mercure sent: post-DELETE for player {user_id}', ['user_id' => $player->getId()]);

        $this->security->logout(validateCsrfToken: false);
        $this->playerRepository->remove($player);
        $this->persistenceAdapter->flush();

        return $this->json(null, Response::HTTP_OK);
    }

    #[Route('/{id}/kill-target-request', name: 'kill_request', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_KILL_PLAYER_UNAUTHORIZED')]
    public function killTarget(Player $player): JsonResponse
    {
        $this->killRequestOnTargetUseCase->execute($player);

        $this->persistenceAdapter->flush();

        $playerTarget = $player->getTarget();
        $this->hub->publish(
            sprintf('player/%s', $playerTarget?->getId()),
            $this->serializer->serialize((object) $playerTarget, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        );

        return $this->json(null, Response::HTTP_OK);
    }

    #[Route('/{id}/switch-mission', name: 'switch_mission', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_SWITCH_MISSION_UNAUTHORIZED')]
    public function switchMission(Player $player): JsonResponse
    {
        try {
            $this->switchMissionUseCase->execute($player);
        } catch (KillerExceptionInterface $e) {
            throw new KillerBadRequestHttpException($e->getMessage());
        }

        $this->hub->publish(
            sprintf('player/%s', $player->getId()),
            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'publish-mercure']),
        );

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'me']);
    }

    #[Route('/{id}/guess-killer', name: 'guess_killer', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_GUESS_KILLER_UNAUTHORIZED')]
    public function guessKiller(Request $request, Player $player): JsonResponse
    {
        $data = $request->toArray();

        if (!isset($data['guessedPlayerId'])) {
            throw new KillerBadRequestHttpException('GUESSED_PLAYER_ID_REQUIRED');
        }

        $guessedPlayerId = $data['guessedPlayerId'];

        try {
            $this->guessKillerUseCase->execute($player, (int) $guessedPlayerId);
        } catch (KillerExceptionInterface $e) {
            throw new KillerBadRequestHttpException($e->getMessage());
        }

        $this->persistenceAdapter->flush();

        $this->eventDispatcher->dispatch(new PlayerUpdatedEvent($player));
        $room = $player->getRoom();

        if ($room !== null) {
            $this->hub->publish(
                sprintf('room/%s', $room->getId()),
                $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
            );
        }

        $this->logger->info('Guess killer request processed for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json(null, Response::HTTP_OK);
    }

    #[Route('/{id}/kill-contest', name: 'kill_contest', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_KILL_CONTEST_UNAUTHORIZED')]
    public function killContest(Player $player): JsonResponse
    {
        try {
            $this->contestKillUseCase->execute($player);
        } catch (KillerExceptionInterface $e) {
            throw new KillerBadRequestHttpException($e->getMessage());
        }

        $this->eventDispatcher->dispatch(new PlayerUpdatedEvent($player));
        $room = $player->getRoom();

        if ($room !== null) {
            $this->hub->publish(
                sprintf('room/%s', $room->getId()),
                $this->serializer->serialize($room, [AbstractNormalizer::GROUPS => 'publish-mercure']),
            );
        }

        $this->logger->info('Kill contest processed for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'me']);
    }
}
