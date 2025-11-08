<?php

declare(strict_types=1);

namespace App\Domain\User;

use App\Domain\User\Entity\User;
use App\Infrastructure\Persistence\BaseRepository;

/** @extends BaseRepository<User> */
interface UserRepository extends BaseRepository
{
    public function findByEmail(string $email): ?User;

    public function findByGoogleId(string $googleId): ?User;

    public function findByAppleId(string $appleId): ?User;
}
