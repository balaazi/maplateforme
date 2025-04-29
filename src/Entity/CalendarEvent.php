<?php
namespace App\Entity;

use App\Repository\CalendarEventRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CalendarEventRepository::class)]
#[ORM\Table(name: 'calendar_event')]
class CalendarEvent
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $title = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $start = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $end = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $googleEventId = null;

    // Getters et Setters...
    public function getId(): ?int
{
    return $this->id;
}

public function getTitle(): ?string
{
    return $this->title;
}

public function setTitle(string $title): self
{
    $this->title = $title;
    return $this;
}

public function getDescription(): ?string
{
    return $this->description;
}

public function setDescription(?string $description): self
{
    $this->description = $description;
    return $this;
}

public function getStart(): ?\DateTimeInterface
{
    return $this->start;
}

public function setStart(\DateTimeInterface $start): self
{
    $this->start = $start;
    return $this;
}

public function getEnd(): ?\DateTimeInterface
{
    return $this->end;
}

public function setEnd(\DateTimeInterface $end): self
{
    $this->end = $end;
    return $this;
}

public function getGoogleEventId(): ?string
{
    return $this->googleEventId;
}

public function setGoogleEventId(?string $googleEventId): self
{
    $this->googleEventId = $googleEventId;
    return $this;
}

}