<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: \App\Repository\ProcesVerbalRepository::class)]
class ProcesVerbal
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\OneToOne(targetEntity: Event::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Event $event = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateHeure = null;

    #[ORM\Column(type: 'text')]
    private ?string $participants = null;

    #[ORM\Column(type: 'text', name: 'points_abordes')]
    private ?string $pointsAbordes = null;

    #[ORM\Column(type: 'text')]
    private ?string $decisionsPrises = null;

    #[ORM\OneToMany(mappedBy: 'procesVerbal', targetEntity: ActionPV::class, cascade: ['persist', 'remove'])]
    private Collection $actions;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $redacteur = null;

    #[ORM\Column(type: 'datetime')]
    private ?\DateTime $dateCreation = null;

    #[ORM\Column(type: 'datetime', nullable: true)]
    private ?\DateTime $dateModification = null;

    #[ORM\Column(type: 'boolean', options: ['default' => false])]
    private bool $finalise = false;

    public function __construct()
    {
        $this->actions = new ArrayCollection();
        $this->dateCreation = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
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

    public function getDateHeure(): ?\DateTime
    {
        return $this->dateHeure;
    }

    public function setDateHeure(?\DateTime $dateHeure): self
    {
        $this->dateHeure = $dateHeure;
        return $this;
    }

    public function getParticipants(): ?string
    {
        return $this->participants;
    }

    public function setParticipants(?string $participants): self
    {
        $this->participants = $participants;
        return $this;
    }

    public function getPointsAbordes(): ?string
    {
        return $this->pointsAbordes;
    }

    public function setPointsAbordes(?string $pointsAbordes): self
    {
        $this->pointsAbordes = $pointsAbordes;
        return $this;
    }

    public function getDecisionsPrises(): ?string
    {
        return $this->decisionsPrises;
    }

    public function setDecisionsPrises(?string $decisionsPrises): self
    {
        $this->decisionsPrises = $decisionsPrises;
        return $this;
    }

    public function getActions(): Collection
    {
        return $this->actions;
    }

    public function addAction(ActionPV $action): self
    {
        if (!$this->actions->contains($action)) {
            $this->actions[] = $action;
            $action->setProcesVerbal($this);
        }
        return $this;
    }

    public function removeAction(ActionPV $action): self
    {
        if ($this->actions->removeElement($action)) {
            if ($action->getProcesVerbal() === $this) {
                $action->setProcesVerbal(null);
            }
        }
        return $this;
    }

    public function getRedacteur(): ?User
    {
        return $this->redacteur;
    }

    public function setRedacteur(?User $redacteur): self
    {
        $this->redacteur = $redacteur;
        return $this;
    }

    public function getDateCreation(): ?\DateTime
    {
        return $this->dateCreation;
    }

    public function setDateCreation(?\DateTime $dateCreation): self
    {
        $this->dateCreation = $dateCreation;
        return $this;
    }

    public function getDateModification(): ?\DateTime
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTime $dateModification): self
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function isFinalise(): bool
    {
        return $this->finalise;
    }

    public function setFinalise(bool $finalise): self
    {
        $this->finalise = $finalise;
        return $this;
    }
}