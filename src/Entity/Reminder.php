<?php

namespace App\Entity;

use App\Repository\ReminderRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReminderRepository::class)]
class Reminder
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
    private ?\DateTimeInterface $dueDate = null;

    #[ORM\Column]
    private bool $isDone = false;

    #[ORM\Column]
    private bool $isTriggered = false;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $triggeredAt = null;

    #[ORM\Column(length: 50)]
    private ?string $type = 'event_reminder';

    #[ORM\Column(length: 20)]
    private ?string $priority = 'normal';

    #[ORM\Column]
    private bool $sendEmail = true;

    #[ORM\Column]
    private bool $showNotification = true;

    #[ORM\Column]
    private bool $playSound = false;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $metadata = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $user = null;

    #[ORM\ManyToOne]
    #[ORM\JoinColumn(nullable: true)]
    private ?Event $event = null;

    public function __construct()
    {
        $this->createdAt = new \DateTime();
        $this->isDone = false;
        $this->isTriggered = false;
        $this->type = 'event_reminder';
        $this->priority = 'normal';
        $this->sendEmail = true;
        $this->showNotification = true;
        $this->playSound = false;
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): static
    {
        $this->description = $description;
        return $this;
    }

    public function getDueDate(): ?\DateTimeInterface
    {
        return $this->dueDate;
    }

    public function setDueDate(\DateTimeInterface $dueDate): static
    {
        $this->dueDate = $dueDate;
        return $this;
    }

    public function isDone(): bool
    {
        return $this->isDone;
    }

    public function setIsDone(bool $isDone): static
    {
        $this->isDone = $isDone;
        return $this;
    }

    public function isTriggered(): bool
    {
        return $this->isTriggered;
    }

    public function setIsTriggered(bool $isTriggered): static
    {
        $this->isTriggered = $isTriggered;
        return $this;
    }

    public function getCreatedAt(): ?\DateTimeInterface
    {
        return $this->createdAt;
    }

    public function setCreatedAt(\DateTimeInterface $createdAt): static
    {
        $this->createdAt = $createdAt;
        return $this;
    }

    public function getTriggeredAt(): ?\DateTimeInterface
    {
        return $this->triggeredAt;
    }

    public function setTriggeredAt(?\DateTimeInterface $triggeredAt): static
    {
        $this->triggeredAt = $triggeredAt;
        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getPriority(): ?string
    {
        return $this->priority;
    }

    public function setPriority(string $priority): static
    {
        $this->priority = $priority;
        return $this;
    }

    public function isSendEmail(): bool
    {
        return $this->sendEmail;
    }

    public function setSendEmail(bool $sendEmail): static
    {
        $this->sendEmail = $sendEmail;
        return $this;
    }

    public function isShowNotification(): bool
    {
        return $this->showNotification;
    }

    public function setShowNotification(bool $showNotification): static
    {
        $this->showNotification = $showNotification;
        return $this;
    }

    public function isPlaySound(): bool
    {
        return $this->playSound;
    }

    public function setPlaySound(bool $playSound): static
    {
        $this->playSound = $playSound;
        return $this;
    }

    public function getMetadata(): ?array
    {
        return $this->metadata;
    }

    public function setMetadata(?array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(?User $user): static
    {
        $this->user = $user;
        return $this;
    }

    public function getEvent(): ?Event
    {
        return $this->event;
    }

    public function setEvent(?Event $event): static
    {
        $this->event = $event;
        return $this;
    }

    /**
     * Vérifie si le rappel doit être déclenché maintenant
     */
    public function shouldTrigger(): bool
    {
        if ($this->isTriggered || $this->isDone) {
            return false;
        }

        $now = new \DateTime();
        return $this->dueDate <= $now;
    }

    /**
     * Marque le rappel comme déclenché
     */
    public function trigger(): static
    {
        $this->isTriggered = true;
        $this->triggeredAt = new \DateTime();
        return $this;
    }

    /**
     * Marque le rappel comme terminé
     */
    public function markAsDone(): static
    {
        $this->isDone = true;
        return $this;
    }

    /**
     * Retourne la configuration de notification pour le frontend
     */
    public function getNotificationConfig(): array
    {
        return [
            'sendEmail' => $this->sendEmail,
            'showNotification' => $this->showNotification,
            'playSound' => $this->playSound,
            'type' => $this->type,
            'priority' => $this->priority,
            'metadata' => $this->metadata
        ];
    }

    /**
     * Retourne un message formaté pour l'affichage
     */
    public function getFormattedMessage(): string
    {
        if ($this->event) {
            return sprintf(
                "Rappel pour l'événement '%s' prévu le %s",
                $this->event->getTitle(),
                $this->event->getDateHeure()->format('d/m/Y à H:i')
            );
        }

        return $this->description ?? $this->title ?? 'Rappel';
    }

    /**
     * Retourne le temps restant avant le rappel
     */
    public function getTimeUntilDue(): ?\DateInterval
    {
        if (!$this->dueDate) {
            return null;
        }

        $now = new \DateTime();
        if ($now > $this->dueDate) {
            return null; // Le rappel est déjà passé
        }

        return $now->diff($this->dueDate);
    }

    /**
     * Retourne true si le rappel est en retard
     */
    public function isOverdue(): bool
    {
        if (!$this->dueDate || $this->isDone || $this->isTriggered) {
            return false;
        }

        $now = new \DateTime();
        return $now > $this->dueDate;
    }
} 