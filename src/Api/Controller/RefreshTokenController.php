<?php

declare(strict_types=1);

namespace App\Api\Controller;

use App\Domain\Player\PlayerRepository;
use App\Infrastructure\Security\RefreshTokenManager;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Psr\Log\LoggerAwareInterface;
use Psr\Log\LoggerAwareTrait;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RefreshTokenController extends AbstractController implements LoggerAwareInterface
{
    use LoggerAwareTrait;

    public function __construct(
        private readonly RefreshTokenManager $refreshTokenManager,
        private readonly JWTTokenManagerInterface $jwtTokenManager,
        private readonly PlayerRepository $playerRepository,
    ) {
    }

    #[Route('/refresh-token', name: 'refresh_token', methods: [Request::METHOD_POST], format: 'json')]
    public function refreshToken(Request $request): JsonResponse
    {
        $data = json_decode($request->getContent(), true);

        if (!isset($data['refresh_token'])) {
            return $this->json(
                ['error' => 'REFRESH_TOKEN_REQUIRED'],
                Response::HTTP_BAD_REQUEST,
            );
        }

        $refreshTokenValue = $data['refresh_token'];
        $refreshToken = $this->refreshTokenManager->validate($refreshTokenValue);

        if ($refreshToken === null) {
            $this->logger->warning('Invalid or expired refresh token provided');

            return $this->json(
                ['error' => 'INVALID_OR_EXPIRED_REFRESH_TOKEN'],
                Response::HTTP_UNAUTHORIZED,
            );
        }

        $player = $refreshToken->getPlayer();

        // Generate new access token
        $accessToken = $this->jwtTokenManager->create($player);

        // Optionally, revoke the old refresh token and create a new one for rotation
        $this->refreshTokenManager->revoke($refreshToken);
        $newRefreshToken = $this->refreshTokenManager->create($player);

        $this->logger->info('Tokens refreshed for player {user_id}', ['user_id' => $player->getId()]);

        return $this->json([
            'token' => $accessToken,
            'refresh_token' => $newRefreshToken->getToken(),
        ], Response::HTTP_OK);
    }
}
