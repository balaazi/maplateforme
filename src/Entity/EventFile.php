<?php
// src/Entity/EventFile.php

namespace App\Entity;

use App\Repository\EventFileRepository;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: EventFileRepository::class)]
#[Vich\Uploadable]
class EventFile
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column]
private ?int $id = null;

#[ORM\Column(length: 255)]
private string $name;

#[ORM\Column(length: 255)]
private string $googleDriveFileId;

#[ORM\ManyToOne(inversedBy: 'files')]
#[ORM\JoinColumn(nullable: false)]
private ?Event $event = null;


    public function getId(): ?int
    {
        return $this->id;
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

    public function getGoogleDriveFileId(): string
    {
        return $this->googleDriveFileId;
    }

    public function setGoogleDriveFileId(string $googleDriveFileId): self
    {
        $this->googleDriveFileId = $googleDriveFileId;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): self
    {
        $this->event = $event;
        return $this;
    }
}
