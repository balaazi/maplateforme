<?php

// src/Entity/Participation.php

namespace App\Entity;

use App\Enum\InvitationStatus;
use App\Repository\ParticipationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: ParticipationRepository::class)]
class Participation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne(inversedBy: 'participations')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(name: 'invitation_status', length: 20)]
    #[Assert\Choice(choices: ['pending', 'accepted', 'declined', 'expired', 'conflict'], message: 'Choix invalide')]
    private ?string $invitationStatus = null;

    #[ORM\Column]
    private ?bool $isPresent = false;

    #[ORM\Column]
    private ?bool $presenceValidated = false;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $createdAt = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $feedback = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): self
    {
        $this->user = $user;

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

    public function getInvitationStatus(): ?string
    {
        return $this->invitationStatus;
    }

    public function setInvitationStatus(string $invitationStatus): self
    {
        $this->invitationStatus = $invitationStatus;

        return $this;
    }

    public function isPresent(): ?bool
    {
        return $this->isPresent;
    }

    public function setIsPresent(bool $isPresent): self
    {
        $this->isPresent = $isPresent;

        return $this;
    }

    public function isPresenceValidated(): ?bool
    {
        return $this->presenceValidated;
    }

    public function setPresenceValidated(bool $presenceValidated): self
    {
        $this->presenceValidated = $presenceValidated;

        return $this;
    }

    public function getCreatedAt(): ?\DateTime
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTime $createdAt): self
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function setFeedback(?string $feedback): self
    {
        $this->feedback = $feedback;

        return $this;
    }
}

