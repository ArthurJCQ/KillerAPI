<?php

declare(strict_types=1);

namespace App\Domain\Player\Entity;

use App\Domain\Mission\Entity\Mission;
use App\Domain\Player\Enum\PlayerStatus;
use App\Domain\Player\Validator\PlayerCanJoinRoom;
use App\Domain\Player\Validator\PlayerCanRename;
use App\Domain\Room\Entity\Room;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[PlayerCanRename]
#[PlayerCanJoinRoom]
class Player implements UserInterface, PasswordAuthenticatedUserInterface
{
    public const ROLE_PLAYER = 'ROLE_PLAYER';
    public const ROLE_ADMIN = 'ROLE_ADMIN';

    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[Groups(['get-player', 'get-room', 'get-mission'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get-player', 'get-room', 'me', 'post-player', 'patch-player'])]
    #[Assert\Length(min: 2)]
    private string $name;

    /** @var string[] */
    #[ORM\Column(type: 'json')]
    #[Groups(['get-player', 'get-room', 'me', 'patch-player'])]
    private array $roles = [];

    #[ORM\Column(
        type: 'string',
        length: 255,
        enumType: PlayerStatus::class,
        options: ['default' => PlayerStatus::ALIVE],
    )]
    #[Groups(['get-player', 'get-room', 'me', 'patch-player'])]
    private PlayerStatus $status = PlayerStatus::ALIVE;

    #[ORM\ManyToOne(targetEntity: Room::class, cascade: ['persist'], inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'room_players', referencedColumnName: 'id')]
    #[Groups(['get-player', 'me', 'patch-player'])]
    private ?Room $room = null;

    #[ORM\OneToOne(mappedBy: 'killer', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'player_target')]
    #[Groups(['me'])]
    private ?Player $target = null;

    #[ORM\OneToOne(inversedBy: 'target', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'player_killer')]
    #[Groups(['me'])]
    private ?Player $killer = null;

    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var Collection<int, Mission> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Mission::class)]
    private Collection $authoredMissions;

    #[ORM\OneToOne(targetEntity: Mission::class, cascade: ['persist', 'remove'])]
    #[ORM\JoinColumn(name: 'user_assigned_mission')]
    #[Groups(['me'])]
    private ?Mission $assignedMission = null;

    #[SerializedName('missions')]
    #[Groups(['me'])]
    private ?array $authoredMissionsInRoom = null;

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
        return $this->name;
    }

    /**
     * @see UserInterface
     * @return string[]
     */
    public function getRoles(): array
    {
        $roles = $this->roles;
        // guarantee every user at least has ROLE_USER
        $roles[] = self::ROLE_PLAYER;

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
        $this->room = $room;

        return $this;
    }

    public function getTarget(): ?self
    {
        return $this->target;
    }

    public function setTarget(?Player $target): self
    {
        $this->target = $target;

        return $this;
    }

    public function getKiller(): ?Player
    {
        return $this->killer;
    }

    public function setKiller(?Player $killer): self
    {
        $this->killer = $killer;

        return $this;
    }

    /** @see PasswordAuthenticatedUserInterface */
    public function getPassword(): string
    {
        return $this->password;
    }

    public function setPassword(string $password): self
    {
        $this->password = $password;

        return $this;
    }

    /** @see UserInterface */
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
            $this->room->addMission($authoredMission);
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
        $this->assignedMission = $assignedMission;

        return $this;
    }

    public function getAuthoredMissionsInRoom(): ?array
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

    public function isAdmin(): bool
    {
        return array_search('ROLE_ADMIN', $this->roles, true);
    }
}
