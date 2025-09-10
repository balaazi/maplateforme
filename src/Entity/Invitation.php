<?php

namespace App\Entity;

use App\Repository\InvitationRepository;
use App\Enum\InvitationStatus;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

#[ORM\Entity(repositoryClass: InvitationRepository::class)]
class Invitation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    #[Assert\Email]
    private ?string $email = null;

    #[ORM\Column(length: 255)]
    #[Assert\NotBlank]
    private ?string $name = null;

    #[ORM\Column(length: 20)]
    #[Assert\Choice(choices: ['pending', 'accepted', 'declined', 'expired', 'conflict'], message: 'Statut invalide')]
    private ?string $status = InvitationStatus::PENDING->value;

    #[ORM\Column(length: 64, unique: true)]
    private ?string $token = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $event = null;

    #[ORM\ManyToOne(inversedBy: 'invitations')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Participant $participant = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->status = InvitationStatus::PENDING->value;
    }

    // Getters & Setters

    public function getId(): ?int { return $this->id; }

    public function getEmail(): ?string { return $this->email; }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getName(): ?string { return $this->name; }

    public function setName(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function getStatus(): ?string { return $this->status; }

    public function setStatus(string $status): static
    {
        // Vérifier si le statut est valide
        if (!in_array($status, [
            InvitationStatus::PENDING->value,
            InvitationStatus::ACCEPTED->value,
            InvitationStatus::DECLINED->value,
            InvitationStatus::EXPIRED->value,
            InvitationStatus::CONFLICT->value
        ])) {
            throw new \InvalidArgumentException("Statut invalide: {$status}");
        }

        // Empêcher la réinitialisation du statut expiré vers en attente
        if ($this->status === InvitationStatus::EXPIRED->value && $status === InvitationStatus::PENDING->value) {
            throw new \InvalidArgumentException("Impossible de réinitialiser une invitation expirée en statut 'en attente'");
        }

        $this->status = $status;
        $this->updatedAt = new \DateTime();
        return $this;
    }

    public function getToken(): ?string { return $this->token; }

    public function setToken(string $token): static
    {
        $this->token = $token;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface { return $this->createdAt; }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface { return $this->updatedAt; }

    public function setUpdatedAt(?\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }

    public function getEvent(): ?Event { return $this->event; }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function getParticipant(): ?Participant { return $this->participant; }

    public function setParticipant(?Participant $participant): static
    {
        $this->participant = $participant;
        return $this;
    }

    // Utility methods
    public function isPending(): bool { return $this->status === InvitationStatus::PENDING->value; }
    public function isAccepted(): bool { return $this->status === InvitationStatus::ACCEPTED->value; }
    public function isDeclined(): bool { return $this->status === InvitationStatus::DECLINED->value; }
    public function isExpired(): bool { return $this->status === InvitationStatus::EXPIRED->value; }
    public function isConflict(): bool { return $this->status === InvitationStatus::CONFLICT->value; }

    /**
     * Vérifie si l'invitation devrait être expirée (30 jours après création)
     */
    public function shouldBeExpired(int $daysExpiration = 30): bool
    {
        if ($this->status !== InvitationStatus::PENDING->value || !$this->createdAt) {
            return false;
        }

        $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
        // Réduire le délai d'expiration pour le test
        $expirationDate = (clone $this->createdAt)->modify("+{$daysExpiration} days");
        
        // Pour le débogage
        error_log(sprintf(
            "Vérification expiration - ID: %d, Email: %s, Créé le: %s, Date expiration: %s, Maintenant: %s",
            $this->id ?? 0,
            $this->email ?? 'unknown',
            $this->createdAt->format('Y-m-d H:i:s'),
            $expirationDate->format('Y-m-d H:i:s'),
            $now->format('Y-m-d H:i:s')
        ));

        return $now > $expirationDate;
    }

    /**
     * Marque l'invitation comme expirée si elle devrait l'être
     */
    public function checkAndMarkAsExpired(int $daysExpiration = 30): bool
    {
        if ($this->shouldBeExpired($daysExpiration)) {
            $this->setStatus(InvitationStatus::EXPIRED->value);
            return true; // L'invitation a été expirée
        }
        return false; // L'invitation n'était pas expirée
    }
}