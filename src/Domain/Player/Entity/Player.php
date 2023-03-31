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
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Serializer\Annotation\MaxDepth;
use Symfony\Component\Serializer\Annotation\SerializedName;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[PlayerCanRename]
class Player implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[Groups(['get-player', 'create-player', 'get-room', 'get-mission', 'me'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Groups(['get-player', 'create-player', 'get-room', 'me', 'post-player', 'patch-player'])]
    #[Assert\Length(min: 2, max: 30, minMessage: 'TOO_SHORT_CONTENT', maxMessage: 'TOO_LONG_CONTENT')]
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
    #[Groups(['get-player', 'create-player', 'get-room', 'me', 'patch-player'])]
    private PlayerStatus $status = PlayerStatus::ALIVE;

    #[ORM\ManyToOne(targetEntity: Room::class, cascade: ['persist'], inversedBy: 'players')]
    #[ORM\JoinColumn(name: 'room_players', referencedColumnName: 'id')]
    #[Groups(['get-player', 'create-player', 'me', 'patch-player'])]
    #[MaxDepth(1)]
    private ?Room $room = null;

    #[ORM\OneToOne(mappedBy: 'killer', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'player_target')]
    #[Groups(['me'])]
    #[MaxDepth(1)]
    private ?Player $target = null;

    #[ORM\OneToOne(inversedBy: 'target', targetEntity: self::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'player_killer')]
    private ?Player $killer = null;

    #[ORM\Column(type: 'string')]
    private string $password;

    /** @var Collection<int, Mission> */
    #[ORM\OneToMany(mappedBy: 'author', targetEntity: Mission::class)]
    private Collection $authoredMissions;

    #[ORM\OneToOne(targetEntity: Mission::class, cascade: ['persist'])]
    #[ORM\JoinColumn(name: 'user_assigned_mission')]
    #[Groups(['me'])]
    private ?Mission $assignedMission = null;

    private string $token = '';

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
        if ($this->room && !$room) {
            $this->room->getPlayers()->removeElement($this);
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
        if ($this->target instanceof self && !$target) {
            $this->target->setKiller(null);
            $this->setKiller(null);
        }

        if ($target instanceof self) {
            $target->setKiller($this);
        }

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
}
