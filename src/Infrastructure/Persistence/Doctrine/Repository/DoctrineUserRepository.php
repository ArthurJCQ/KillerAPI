<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\User\Entity\User;
use App\Domain\User\UserRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<User> */
final class DoctrineUserRepository extends DoctrineBaseRepository implements UserRepository
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, User::class);
    }

    public function findByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
    }

    public function findByGoogleId(string $googleId): ?User
    {
        return $this->findOneBy(['googleId' => $googleId]);
    }

    public function findByAppleId(string $appleId): ?User
    {
        return $this->findOneBy(['appleId' => $appleId]);
    }
}
