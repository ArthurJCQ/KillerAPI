<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\ValidationException;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Player\Security\PlayerVoter;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Serializer\KillerSerializer;
use App\Validator\KillerValidator;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Sensio\Bundle\FrameworkExtraBundle\Configuration\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\HttpKernel\Exception\UnauthorizedHttpException;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[Route('/player')]
class PlayerController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly HubInterface $hub,
        private readonly KillerSerializer $serializer,
        private readonly KillerValidator $validator,
        private readonly JWTTokenManagerInterface $tokenManager,
    ) {
    }

    #[Route(name: 'create_player', methods: [Request::METHOD_POST])]
    public function createPlayer(Request $request): JsonResponse
    {
        $player = $this->serializer->deserialize(
            (string) $request->getContent(),
            Player::class,
            [AbstractNormalizer::GROUPS => 'post-player'],
        );

        // random password is set on prePersist event. Here is just for validation. To remove if possible.
        $player->setPassword('tempP@$$w0rd');

        try {
            $this->validator->validate($player);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

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
            throw new NotFoundHttpException('Player not found.');
        }

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'me']);
    }

    #[Route('/{id}', name: 'get_player', methods: [Request::METHOD_GET])]
    public function getPlayerById(Player $player): JsonResponse
    {
        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-player']);
    }

    #[Route('/{id}', name: 'patch_player', methods: [Request::METHOD_PATCH])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player')]
    public function patchPlayer(Request $request, Player $player): JsonResponse
    {
        $data = $request->toArray();

        if (isset($data['role']) && $player !== $player->getRoom()?->getAdmin()) {
            throw new UnauthorizedHttpException('Can not update player role with non admin player');
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

        try {
            $this->validator->validate($player);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->persistenceAdapter->flush();

        // TODO: publish event for previous room if there is one
        $this->hub->publish(new Update(
            sprintf('room/%s', $player->getRoom()),
            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'get-player']),
        ));
        $this->logger->info('Event mercure sent: post-PATCH for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-player']);
    }

    #[Route('/{id}', name: 'delete_player', methods: [Request::METHOD_DELETE])]
    #[IsGranted(PlayerVoter::EDIT_PLAYER, subject: 'player')]
    public function deletePlayer(Player $player): JsonResponse
    {
        $room = $player->getRoom();

        $this->playerRepository->remove($player);

        $this->hub->publish(new Update(
            sprintf('room/%s', $room),
            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'get-player']),
        ));
        $this->logger->info('Event mercure sent: post-DELETE for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
