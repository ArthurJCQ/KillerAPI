<?php

declare(strict_types=1);

namespace App\Domain\Room\Entity;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Validator\CanPatchRoom;
use App\Infrastructure\Persistence\Doctrine\RoomIdGenerator;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[CanPatchRoom]
class Room
{
    public const string PENDING = 'PENDING';
    public const string IN_GAME = 'IN_GAME';
    public const string ENDED = 'ENDED';

    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 5, unique: true)]
    #[ORM\GeneratedValue(strategy: 'CUSTOM')]
    #[ORM\CustomIdGenerator(RoomIdGenerator::class)]
    #[Assert\Length(exactly: 5)]
    #[Groups(['get-player', 'get-room', 'get-mission', 'me', 'publish-mercure', 'patch-player'])]
    private string $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get-room', 'me', 'patch-room', 'publish-mercure'])]
    #[Assert\Length(min: 2, max: 50, minMessage: 'TOO_SHORT_CONTENT', maxMessage: 'TOO_LONG_CONTENT')]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => self::PENDING])]
    #[Groups(['get-room', 'me', 'publish-mercure'])]
    private string $status = self::PENDING;

    /** @var Collection<int, Player> */
    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Player::class, fetch: 'EAGER')]
    #[Assert\Unique]
    #[Groups(['get-room', 'publish-mercure'])]
    private Collection $players;

    #[ORM\OneToOne(targetEntity: Player::class)]
    #[Groups(['get-room', 'publish-mercure'])]
    private ?Player $admin = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dateEnd;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Mission::class, cascade: ['remove'])]
    #[Groups(['get-room', 'publish-mercure'])]
    private Collection $missions;

    /** @var Collection<int, Mission> */
    #[ORM\ManyToMany(targetEntity: Mission::class, cascade: ['remove'])]
    #[ORM\JoinTable(name: 'room_secondary_missions')]
    #[ORM\JoinColumn(name: 'room_id', referencedColumnName: 'id')]
    #[ORM\InverseJoinColumn(name: 'mission_id', referencedColumnName: 'id', unique: true)]
    #[Groups(['get-room', 'publish-mercure'])]
    private Collection $secondaryMissions;

    #[ORM\ManyToOne(targetEntity: Player::class)]
    #[Groups(['get-room', 'publish-mercure'])]
    private ?Player $winner = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    #[Groups(['get-room', 'publish-mercure', 'me'])]
    private bool $isGameMastered = false;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->dateEnd = new \DateTime('+30days');
        $this->missions = new ArrayCollection();
        $this->secondaryMissions = new ArrayCollection();
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function setId(string $id): self
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

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;

        return $this;
    }

    /** @return Collection<int, Player> */
    public function getPlayers(): Collection
    {
        return $this->players;
    }

    public function getAlivePlayers(): array
    {
        return array_values(
            array_filter(
                $this->players->toArray(),
                static fn (Player $player) => $player->getStatus() === PlayerStatus::ALIVE,
            ),
        );
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->setRoom($this);
        }

        if ($this->players->count() === 1) {
            $this->setAdmin($player);
        }

        return $this;
    }

    public function removePlayer(Player $player): self
    {
        if ($this->players->removeElement($player)) {
            // set the owning side to null (unless already changed)
            if ($player->getRoom() === $this) {
                $player->setRoom(null);
            }

            if ($this->admin === $player) {
                $this->setAdmin(null);
            }

            foreach ($player->getAuthoredMissions() as $mission) {
                $this->removeMission($mission);
            }
        }

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeImmutable $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getDateEnd(): ?\DateTime
    {
        return $this->dateEnd;
    }

    public function setDateEnd(\DateTime $dateEnd): self
    {
        $this->dateEnd = $dateEnd;

        return $this;
    }

    public function getMissions(): Collection
    {
        return $this->missions;
    }

    public function addMission(Mission $mission): self
    {
        if (!$this->missions->contains($mission)) {
            $this->missions[] = $mission;
            $mission->setRoom($this);
        }

        return $this;
    }

    public function removeMission(Mission $mission): self
    {
        if ($this->missions->removeElement($mission)) {
            // set the owning side to null (unless already changed)
            if ($mission->getRoom() === $this) {
                $mission->setRoom(null);
            }
        }

        return $this;
    }

    public function getAdmin(): ?Player
    {
        return $this->admin;
    }

    public function setAdmin(?Player $admin): self
    {
        $this->admin = $admin;

        return $this;
    }

    public function getWinner(): ?Player
    {
        return $this->winner;
    }

    public function setWinner(?Player $winner): self
    {
        $this->winner = $winner;

        return $this;
    }

    public function isGameMastered(): bool
    {
        return $this->isGameMastered;
    }

    public function setIsGameMastered(bool $isGameMastered): self
    {
        $this->isGameMastered = $isGameMastered;

        return $this;
    }

    /** @return Collection<int, Mission> */
    public function getSecondaryMissions(): Collection
    {
        return $this->secondaryMissions;
    }

    public function addSecondaryMission(Mission $mission): self
    {
        if (!$this->secondaryMissions->contains($mission)) {
            $this->secondaryMissions[] = $mission;
        }

        return $this;
    }

    public function removeSecondaryMission(Mission $mission): self
    {
        $this->secondaryMissions->removeElement($mission);

        return $this;
    }

    public function popSecondaryMission(): ?Mission
    {
        if ($this->secondaryMissions->isEmpty()) {
            return null;
        }

        /** @var Mission $mission */
        $mission = $this->secondaryMissions->first();
        $this->removeSecondaryMission($mission);

        return $mission;
    }

    public function __toString(): string
    {
        return $this->getId();
    }
}
