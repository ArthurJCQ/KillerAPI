<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Api\Exception\ValidationException;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\PlayerRepository;
use App\Domain\Player\Service\PlayerUpdater;
use App\Domain\Player\UseCase\DeletePlayerUseCase;
use App\Infrastructure\Persistence\PersistenceAdapterInterface;
use App\Serializer\KillerSerializer;
use App\Validator\KillerValidator;
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
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;

#[Route('/player')]
class PlayerController extends AbstractController
{
    public function __construct(
        private readonly PlayerRepository $playerRepository,
        private readonly PersistenceAdapterInterface $persistenceAdapter,
        private readonly PlayerUpdater $playerUpdater,
        private readonly DeletePlayerUseCase $deletePlayerUseCase,
        private readonly HubInterface $hub,
        private readonly KillerSerializer $serializer,
        private readonly KillerValidator $validator,
        private readonly TokenStorageInterface $tokenStorage,
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
        $player->setPassword('tempP@$$w0rd');

        try {
            $this->validator->validate($player);
        } catch (ValidationException $e) {
            throw new BadRequestHttpException($e->getMessage());
        }

        $this->playerRepository->store($player);
        $this->persistenceAdapter->flush();


        $this->tokenStorage->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));

//        $this->hub->publish(new Update(
//            sprintf('room/%s', $player->getRoom()),
//            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'get-player']),
//        ));

        return $this->json(
            $player,
            Response::HTTP_CREATED,
            ['Location' => sprintf('/player/%s', $player->getUserIdentifier())],
            [AbstractNormalizer::GROUPS => 'get-player'],
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
    public function getPlayerById(?Player $player): JsonResponse
    {
        if (!$player) {
            throw new NotFoundHttpException('Player not found');
        }

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-player']);
    }

    #[Route(name: 'patch_player', methods: [Request::METHOD_PATCH])]
    public function patchPlayer(Request $request): JsonResponse
    {
        $player = $this->getUser();

        if (!$player instanceof Player) {
            throw new NotFoundHttpException('Player not found');
        }

        $data = $request->toArray();

        if (isset($data['role']) && !$this->isGranted(Player::ROLE_ADMIN)) {
            throw new UnauthorizedHttpException('Can not update player role with non admin player');
        }

        try {
            // TODO: Pre update event ?
            $this->playerUpdater->handleUpdate($data, $player);
        } catch (\DomainException $e) {
            throw new BadRequestHttpException($e->getMessage());
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

        // TODO Find a solution to do it in a post flush event
        $this->tokenStorage->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));

        // TODO: publish event for previous room if there is one
//        $this->hub->publish(new Update(
//            sprintf('room/%s', $player->getRoom()),
//            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'get-player']),
//        ));

        return $this->json($player, Response::HTTP_OK, [], [AbstractNormalizer::GROUPS => 'get-player']);
    }

    #[Route(name: 'delete_player', methods: [Request::METHOD_DELETE])]
    public function deletePlayer(): JsonResponse
    {
        $player = $this->getUser();

        if (!$player instanceof Player) {
            throw new NotFoundHttpException('Player not found');
        }

        $room = $player->getRoom();

        $this->deletePlayerUseCase->execute($player);

        $this->hub->publish(new Update(
            sprintf('room/%s', $room),
            $this->serializer->serialize($player, [AbstractNormalizer::GROUPS => 'get-player']),
        ));

        return $this->json(null, Response::HTTP_NO_CONTENT);
    }
}
