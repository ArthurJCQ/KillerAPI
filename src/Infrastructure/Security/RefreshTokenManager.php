<?php

declare(strict_types=1);

namespace App\Infrastructure\Security;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Entity\RefreshToken;
use App\Domain\Player\RefreshTokenRepository;
use App\Infrastructure\Persistence\Doctrine\DoctrinePersistenceAdapter;

final class RefreshTokenManager
{
    // 6 months in seconds
    private const int REFRESH_TOKEN_TTL = 15552000; // 180 days

    public function __construct(
        private readonly RefreshTokenRepository $refreshTokenRepository,
        private readonly DoctrinePersistenceAdapter $persistenceAdapter,
    ) {
    }

    public function create(Player $player): RefreshToken
    {
        $token = $this->generateToken();
        $expiresAt = new \DateTimeImmutable(sprintf('+%d seconds', self::REFRESH_TOKEN_TTL));

        $refreshToken = new RefreshToken($player, $token, $expiresAt);
        $this->refreshTokenRepository->store($refreshToken);
        $this->persistenceAdapter->flush();

        return $refreshToken;
    }

    public function validate(string $token): ?RefreshToken
    {
        $refreshToken = $this->refreshTokenRepository->findByToken($token);

        if ($refreshToken === null || $refreshToken->isExpired()) {
            return null;
        }

        return $refreshToken;
    }

    public function revoke(RefreshToken $refreshToken): void
    {
        $this->refreshTokenRepository->remove($refreshToken);
        $this->persistenceAdapter->flush();
    }

    public function revokeAllForPlayer(Player $player): void
    {
        $refreshTokens = $this->refreshTokenRepository->findByPlayer($player);

        foreach ($refreshTokens as $refreshToken) {
            $this->refreshTokenRepository->remove($refreshToken);
        }

        $this->persistenceAdapter->flush();
    }

    public function cleanupExpired(): int
    {
        return $this->refreshTokenRepository->deleteExpired();
    }

    private function generateToken(): string
    {
        return bin2hex(random_bytes(32));
    }
}
