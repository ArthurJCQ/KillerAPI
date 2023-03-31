<?php

declare(strict_types=1);

namespace App\Domain\Room\Entity;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Entity\Player;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Room\Validator\CanPatchRoom;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[UniqueEntity(fields: 'code')]
#[CanPatchRoom]
class Room
{
    public const PENDING = 'PENDING';
    public const IN_GAME = 'IN_GAME';
    public const ENDED = 'ENDED';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[Groups(['get-player', 'get-room', 'get-mission', 'me'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 5)]
    #[Groups(['get-room', 'me', 'get-player', 'get-mission'])]
    #[Assert\Length(exactly: 5)]
    private string $code;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get-room', 'me', 'post-room', 'patch-room'])]
    #[Assert\Length(min: 2, max: 50, minMessage: 'TOO_SHORT_CONTENT', maxMessage: 'TOO_LONG_CONTENT')]
    private string $name;

    #[ORM\Column(type: 'string', length: 255, options: ['default' => self::PENDING])]
    #[Groups(['get-room', 'me'])]
    private string $status = self::PENDING;

    /** @var Collection<int, Player> */
    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Player::class)]
    #[Groups('get-room')]
    private Collection $players;

    #[ORM\OneToOne(targetEntity: Player::class)]
    #[Groups(['get-room'])]
    private ?Player $admin = null;

    #[ORM\Column(type: 'datetime_immutable')]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(type: 'datetime')]
    private \DateTime $dateEnd;

    #[ORM\OneToMany(mappedBy: 'room', targetEntity: Mission::class, cascade: ['remove'])]
    #[Groups(['get-room', 'me'])]
    private Collection $missions;

    #[ORM\OneToOne(targetEntity: Player::class)]
    #[Groups(['get-room'])]
    private ?Player $winner = null;

    public function __construct()
    {
        $this->players = new ArrayCollection();
        $this->createdAt = new \DateTimeImmutable();
        $this->dateEnd = new \DateTime('+30days');
        $this->missions = new ArrayCollection();
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

    public function getCode(): string
    {
        return $this->code;
    }

    public function setCode(string $code): self
    {
        $this->code = $code;

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

    /** @return ?array<int, Player> */
    public function getAlivePlayers(): ?array
    {
        return array_values(
            array_filter($this->players->toArray(), static fn (Player $player) =>
                $player->getStatus() === PlayerStatus::ALIVE),
        );
    }

    public function addPlayer(Player $player): self
    {
        if (!$this->players->contains($player)) {
            $this->players[] = $player;
            $player->setRoom($this);
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

    public function __toString(): string
    {
        return $this->getCode();
    }

    /**
     * @return Collection<int, Mission>
     */
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
}
