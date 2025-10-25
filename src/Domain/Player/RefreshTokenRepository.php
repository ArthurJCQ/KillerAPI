<?php

declare(strict_types=1);

namespace App\Domain\Player;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Entity\RefreshToken;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<RefreshToken> */
interface RefreshTokenRepository extends BaseRepository
{
    public function findByToken(string $token): ?RefreshToken;

    /** @return RefreshToken[] */
    public function findByPlayer(Player $player): array;

    public function deleteExpired(): int;
}
