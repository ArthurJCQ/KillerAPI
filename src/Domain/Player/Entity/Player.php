<?php

declare(strict_types=1);

namespace App\Domain\Player\Entity;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Validator\PlayerCanRename;
use App\Domain\Room\Entity\Room;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Notifier\Recipient\Recipient;
use Symfony\Component\Notifier\Recipient\RecipientInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[PlayerCanRename]
class Player implements UserInterface, RecipientInterface
{
    public const string DEFAULT_AVATAR = 'captain';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: 'SEQUENCE')]
    #[Groups(['get-player', 'create-player', 'get-room', 'get-mission', 'me', 'publish-mercure'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get-player', 'create-player', 'get-room', 'me', 'post-player', 'patch-player', 'publish-mercure'])]
    #[Assert\Length(
        min: 2,
        max: 30,
        minMessage: 'PLAYER_NAME_TOO_SHORT_CONTENT',
        maxMessage: 'PLAYER_NAME_TOO_LONG_CONTENT',
    )]
    private string $name;

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    private array $roles = ['ROLE_USER'];

    #[ORM\Column(
        type: 'string',
        length: 255,
        enumType: PlayerStatus::class,
        options: ['default' => PlayerStatus::ALIVE],
    )]
    #[Groups(['get-player', 'create-player', 'get-room', 'me', 'patch-player', 'publish-mercure'])]
    private PlayerStatus $status = PlayerStatus::ALIVE;

    #[ORM\ManyToOne(targetEntity: Room::class, cascade: ['persist'], inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'room_players')]
    #[Groups(['get-player', 'create-player', 'me'])]
    #[MaxDepth(1)]
    private ?Room $room = null;

    #[ORM\OneToOne(mappedBy: 'killer', targetEntity: self::class, cascade: ['persist'])]
    #[Groups(['me', 'get-player-master', 'get-room-master'])]
    #[MaxDepth(1)]
    private ?Player $target = null;

    #[ORM\OneToOne(inversedBy: 'target', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'player_killer')]
    private ?Player $killer = null;

    /** @var Collection<int, Mission> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Mission::class)]
    private Collection $authoredMissions;

    #[ORM\OneToOne(targetEntity: Mission::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_assigned_mission')]
    #[Groups(['me', 'get-player-master', 'get-room-master'])]
    private ?Mission $assignedMission = null;

    #[ORM\Column(type: 'string', options: ['default' => self::DEFAULT_AVATAR])]
    #[Groups(['me', 'get-room', 'post-player', 'create-player', 'get-player', 'patch-player'])]
    private string $avatar = self::DEFAULT_AVATAR;

    #[ORM\Column(type: 'string', options: ['default' => ''])]
    #[Groups(['me', 'post-player', 'create-player', 'patch-player'])]
    private string $expoPushToken = '';

    #[ORM\Column(type: 'integer', options: ['default' => 0])]
    #[Groups(['me', 'get-player', 'get-room'])]
    private int $points = 0;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['me'])]
    private bool $missionSwitchUsed = false;

    private string $token = '';

    private string $refreshToken = '';

    public function __construct()
    {
        $this->authoredMissions = new ArrayCollection();
    }

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    /** @see UserInterface */
    public function getUserIdentifier(): string
    {
        return (string) $this->id;
    }

    /**
     * @see UserInterface
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = 'ROLE_USER';

        return array_unique($roles);
    }

    /** @param string[] $roles */
    public function setRoles(array $roles): self
    {
        $this->roles = $roles;

        return $this;
    }

    public function getStatus(): PlayerStatus
    {
        return $this->status;
    }

    public function setStatus(PlayerStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getRoom(): ?Room
    {
        return $this->room;
    }

    public function setRoom(?Room $room): self
    {
        // Player changes room
        if ($this->room !== $room) {
            $this->clearMissions();

            if ($this->room?->getAdmin() === $this) {
                $this->room->setAdmin(null);
            }
        }

        $this->room = $room;

        return $this;
    }

    public function getTarget(): ?self
    {
        return $this->target;
    }

    public function setTarget(?Player $target): self
    {
        $this->target?->setKiller(null);

        // New target for the player
        $target?->setKiller($this);

        $this->target = $target;

        return $this;
    }

    public function getKiller(): ?Player
    {
        return $this->killer;
    }

    public function setKiller(?Player $killer): self
    {
        // If we're removing the killer, clear the inverse side
        if ($killer === null && $this->killer !== null) {
            // Only clear if we're still the target
            if ($this->killer->getTarget() === $this) {
                $this->killer->target = null;
            }
        }

        // If we're setting a new killer, update the inverse side
        if ($killer !== null && $killer !== $this->killer) {
            // Clear the old killer's target if we're still their target
            if ($this->killer !== null && $this->killer->getTarget() === $this) {
                $this->killer->target = null;
            }
            // Set the new killer's target to this player
            $killer->target = $this;
        }

        $this->killer = $killer;

        return $this;
    }

    /** @see UserInterface */
    #[\Deprecated]
    public function eraseCredentials(): void
    {
        // If you store any temporary, sensitive data on the user, clear it here
    }

    /** @return Collection<int, Mission> */
    public function getAuthoredMissions(): Collection
    {
        return $this->authoredMissions;
    }

    public function addAuthoredMission(Mission $authoredMission): self
    {
        if (!$this->authoredMissions->contains($authoredMission)) {
            $this->authoredMissions[] = $authoredMission;
            $authoredMission->setAuthor($this);
            $this->room?->addMission($authoredMission);
        }

        return $this;
    }

    public function removeAuthoredMission(Mission $authoredMission): self
    {
        if ($this->authoredMissions->removeElement($authoredMission)) {
            // set the owning side to null (unless already changed)
            if ($authoredMission->getAuthor() === $this) {
                $authoredMission->setAuthor(null);
            }
        }

        return $this;
    }

    public function getAssignedMission(): ?Mission
    {
        return $this->assignedMission;
    }

    public function setAssignedMission(?Mission $assignedMission): self
    {
        $this->assignedMission?->setIsAssigned(false);
        $assignedMission?->setIsAssigned(true);

        $this->assignedMission = $assignedMission;

        return $this;
    }

    public function getAvatar(): string
    {
        return $this->avatar;
    }

    public function setAvatar(string $avatar): self
    {
        $this->avatar = $avatar;

        return $this;
    }

    /** @return Mission[] */
    #[SerializedName('authoredMissions')]
    #[Groups(['me'])]
    public function getAuthoredMissionsInRoom(): array
    {
        $missions = [];

        foreach ($this->getAuthoredMissions() as $mission) {
            if ($mission->getRoom() !== $this->getRoom()) {
                continue;
            }

            $missions[] = $mission;
        }

        return $missions;
    }

    public function clearMissions(): self
    {
        foreach ($this->getAuthoredMissions() as $mission) {
            if (!$this->authoredMissions->removeElement($mission) || $mission->getAuthor() !== $this) {
                continue;
            }

            $mission->setAuthor(null);
            $this->room?->removeMission($mission);
        }

        return $this;
    }

    #[Groups(['create-player'])]
    public function getToken(): string
    {
        return $this->token;
    }

    public function setToken(string $token): self
    {
        $this->token = $token;

        return $this;
    }

    #[Groups(['create-player'])]
    public function getRefreshToken(): string
    {
        return $this->refreshToken;
    }

    public function setRefreshToken(string $refreshToken): self
    {
        $this->refreshToken = $refreshToken;

        return $this;
    }

    public function getExpoPushToken(): string
    {
        return $this->expoPushToken;
    }

    public function setExpoPushToken(string $expoPushToken): self
    {
        $this->expoPushToken = $expoPushToken;

        return $this;
    }

    public function getRecipient(): Recipient
    {
        return new Recipient($this->name);
    }

    #[SerializedName('hasAtLeastOneMission')]
    #[Groups(['get-room'])]
    public function hasAtLeastOneMission(): bool
    {
        return \count($this->getAuthoredMissionsInRoom()) > 0;
    }

    public function getPoints(): int
    {
        return $this->points;
    }

    public function setPoints(int $points): self
    {
        $this->points = $points;

        return $this;
    }

    public function addPoints(int $points): self
    {
        $this->points += $points;

        return $this;
    }

    public function removePoints(int $points): self
    {
        $this->points -= $points;

        return $this;
    }

    public function hasMissionSwitchUsed(): bool
    {
        return $this->missionSwitchUsed;
    }

    public function setMissionSwitchUsed(bool $missionSwitchUsed): self
    {
        $this->missionSwitchUsed = $missionSwitchUsed;

        return $this;
    }
}
