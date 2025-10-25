<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence\Doctrine\Repository;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Entity\RefreshToken;
use App\Domain\Player\RefreshTokenRepository;
use Doctrine\ORM\EntityManagerInterface;

/** @extends DoctrineBaseRepository<RefreshToken> */
final class DoctrineRefreshTokenRepository extends DoctrineBaseRepository implements RefreshTokenRepository
{
    public function __construct(protected EntityManagerInterface $entityManager)
    {
        parent::__construct($entityManager, RefreshToken::class);
    }

    public function findByToken(string $token): ?RefreshToken
    {
        return $this->findOneBy(['token' => $token]);
    }

    /** @return RefreshToken[] */
    public function findByPlayer(Player $player): array
    {
        return $this->findBy(['player' => $player]);
    }

    public function deleteExpired(): int
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(RefreshToken::class, 'rt')
            ->where('rt.expiresAt < :now')
            ->setParameter('now', new \DateTimeImmutable());

        return $qb->getQuery()->execute();
    }
}
