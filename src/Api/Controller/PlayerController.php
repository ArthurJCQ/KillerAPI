<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Application\UseCase\Player\ChangeRoomUseCase;
use App\Application\UseCase\Player\KillRequestOnTargetUseCase;
use App\Domain\KillerSerializerInterface;
use App\Domain\KillerValidatorInterface;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Event\PlayerKilledEvent;
use App\Domain\Player\Event\PlayerUpdatedEvent;
use App\Domain\Player\PlayerRepository;
use App\Domain\Room\Entity\Room;
use App\Domain\Room\RoomRepository;
use App\Domain\Room\RoomWorkflowTransitionInterface;
use App\Infrastructure\Http\Cookie\CookieProvider;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Infrastructure\Security\Voters\PlayerVoter;
use App\Infrastructure\SSE\SseInterface;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Exception\BadRequestException;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
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
        private readonly RoomRepository $roomRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly SseInterface $hub,
        private readonly KillerSerializerInterface $serializer,
        private readonly KillerValidatorInterface $validator,
        private readonly JWTTokenManagerInterface $tokenManager,
        private readonly RoomWorkflowTransitionInterface $roomStatusTransitionUseCase,
        private readonly EventDispatcherInterface $eventDispatcher,
        private readonly Security $security,
        private readonly ChangeRoomUseCase $changeRoomUseCase,
        private readonly KillRequestOnTargetUseCase $killRequestOnTargetUseCase,
    ) {
    }

    #[Route(name: 'create_player', methods: [Request::METHOD_POST])]
    public function createPlayer(
        #[MapRequestPayload(serializationContext: [AbstractNormalizer::GROUPS => 'post-player'])] Player $player,
    ): JsonResponse {
        $this->playerRepository->store($player);
        $this->persistenceAdapter->flush();

        $player->setToken($this->tokenManager->create($player));
        $this->logger->info('Token created for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json(
            $player,
            Response::HTTP_CREATED,
            ['Location' => sprintf('/player/%s', $player->getUserIdentifier())],
            [AbstractNormalizer::GROUPS => 'create-player'],
        );
    }

    #[Route('/me', name: 'me', methods: [Request::METHOD_GET])]
    public function me(): JsonResponse
    {
        $player = $this->getUser();

        if ($player === null) {
            throw new NotFoundHttpException('KILLER_PLAYER_NOT_FOUND');
        }

        $response = $this->json(
            $player,
            Response::HTTP_OK,
            [],
            [
                AbstractNormalizer::GROUPS => 'me',
                AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
            ],
        );

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

        // If room is about to be updated, keep the reference of the previous one
        $previousRoom = $player->getRoom();

        if (array_key_exists('room', $data)) {
            $newRoom = $this->roomRepository->find($data['room']);

            if (!$newRoom && $data['room'] !== null) {
                throw $this->createNotFoundException('ROOM_NOT_FOUND');
            }

            $this->changeRoomUseCase->execute($player, $newRoom);
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
            sprintf('room/%s', $player->getRoom()),
            $this->serializer->serialize(
                (object) $player->getRoom(),
                [AbstractNormalizer::GROUPS => 'publish-mercure'],
            ),
        );

        if ($previousRoom !== $player->getRoom()) {
            $this->hub->publish(
                sprintf('room/%s', $previousRoom),
                $this->serializer->serialize((object) $previousRoom, [AbstractNormalizer::GROUPS => 'publish-mercure']),
            );
        }

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

    #[Route('/{id}/kill-request', name: 'kill_request', methods: [Request::METHOD_POST])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player', message: 'KILLER_KILL_PLAYER_UNAUTHORIZED')]
    public function killRequest(Player $player): JsonResponse
    {
        $this->killRequestOnTargetUseCase->execute($player);

        return $this->json(null, Response::HTTP_OK);
    }
}
