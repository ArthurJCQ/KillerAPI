<?php

declare(strict_types=1);

namespace App\Subscriber;

use App\Entity\Player;
use App\Entity\Room;
use App\Repository\MissionRepository;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

class PlayerDoctrineSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly MissionRepository $missionRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function preRemove(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player) {
            return;
        }

        $playerRoom = $player->getRoom();

        if (!$playerRoom || $playerRoom->getStatus() !== Room::PENDING) {
            return;
        }

        foreach ($player->getAuthoredMissions() as $mission) {
            $this->missionRepository->remove($mission);
        }
    }

    // NOT SURE IT REALLY WORKS (Test are failing for instance. User is reauthenticated in controllers as well for now).
    public function postUpdate(LifecycleEventArgs $args): void
    {
        $player = $args->getObject();

        if (!$player instanceof Player) {
            return;
        }

        $uow = $this->entityManager->getUnitOfWork();
        $changeSet = $uow->getEntityChangeSet($player);

        // If roles have been changed, symfony deauthenticate the user.
        if (!isset($changeSet['roles'])) {
            return;
        }

        // Should we rather do it on post flush ? If so, can't do it in a subscriber
        $this->tokenStorage->setToken(new UsernamePasswordToken($player, 'main', $player->getRoles()));
    }

    public function getSubscribedEvents(): array
    {
        return [Events::preRemove, Events::postUpdate];
    }
}
