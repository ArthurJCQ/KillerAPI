<?php

declare(strict_types=1);

namespace App\Domain\Mission\Entity;

use App\Domain\Player\Entity\Player;
use App\Domain\Player\Validator\PlayerCanUpdateMission;
use App\Domain\Room\Entity\Room;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Serializer\Annotation\Groups;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity]
#[PlayerCanUpdateMission(groups: ['patch_mission'])]
class Mission
{
    #[ORM\Id]
    #[ORM\Column(type: 'integer', unique: true)]
    #[ORM\GeneratedValue(strategy: "AUTO")]
    #[Groups(['get-player', 'get-room', 'get-mission', 'me'])]
    private int $id;

    #[ORM\Column(type: 'string', length: 255)]
    #[Assert\Length(min: 5, max: 1000, minMessage: 'MISSION_TOO_SHORT_CONTENT', maxMessage: 'MISSION_TOO_LONG_CONTENT')]
    #[Groups(['get-mission', 'get-player', 'post-mission', 'me'])]
    private string $content;

    #[ORM\ManyToOne(targetEntity: Player::class, cascade: ['persist'], inversedBy: 'authoredMissions')]
    #[ORM\JoinColumn(name: 'user_authored_missions')]
    #[Groups(['get-mission', 'get-player'])]
    private ?Player $author;

    #[ORM\ManyToOne(targetEntity: Room::class, cascade: ['persist'], inversedBy: 'missions')]
    #[ORM\JoinColumn(name: 'room_missions')]
    #[Groups(['get-mission', 'get-player'])]
    private ?Room $room;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $isAssigned = false;

    public function getId(): int
    {
        return $this->id;
    }

    public function setId(int $id): self
    {
        $this->id = $id;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getAuthor(): ?Player
    {
        return $this->author;
    }

    public function setAuthor(?Player $author): self
    {
        $this->author = $author;

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

    public function isAssigned(): bool
    {
        return $this->isAssigned;
    }

    public function setIsAssigned(bool $isAssigned): self
    {
        $this->isAssigned = $isAssigned;

        return $this;
    }
}
