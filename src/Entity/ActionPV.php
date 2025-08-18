<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: \App\Repository\ActionPVRepository::class)]
class ActionPV
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: 'integer')]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: ProcesVerbal::class, inversedBy: 'actions')]
    #[ORM\JoinColumn(nullable: false)]
    private ?ProcesVerbal $procesVerbal = null;

    #[ORM\Column(type: 'text')]
    private ?string $description = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?User $responsable = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $responsableNom = null;

    #[ORM\Column(type: 'date', nullable: true)]
    private ?\DateTime $delai = null;

    #[ORM\Column(type: 'string', length: 50, options: ['default' => 'en_attente'])]
    private string $statut = 'en_attente';

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $notes = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProcesVerbal(): ?ProcesVerbal
    {
        return $this->procesVerbal;
    }

    public function setProcesVerbal(?ProcesVerbal $procesVerbal): self
    {
        $this->procesVerbal = $procesVerbal;
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

    public function getResponsable(): ?User
    {
        return $this->responsable;
    }

    public function setResponsable(?User $responsable): self
    {
        $this->responsable = $responsable;
        return $this;
    }

    public function getResponsableNom(): ?string
    {
        return $this->responsableNom;
    }

    public function setResponsableNom(?string $responsableNom): self
    {
        $this->responsableNom = $responsableNom;
        return $this;
    }

    public function getDelai(): ?\DateTime
    {
        return $this->delai;
    }

    public function setDelai(?\DateTime $delai): self
    {
        $this->delai = $delai;
        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): self
    {
        $this->statut = $statut;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        return $this;
    }
}