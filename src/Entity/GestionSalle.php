<?php

namespace App\Entity;

use App\Repository\GestionSalleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: GestionSalleRepository::class)]
class GestionSalle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salle $salle = null;

    #[ORM\Column(length: 255)]
    private ?string $responsable = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateGestion = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $commentaire = null;

    #[ORM\Column(length: 50)]
    private ?string $statut = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSalle(): ?Salle
    {
        return $this->salle;
    }

    public function setSalle(?Salle $salle): static
    {
        $this->salle = $salle;

        return $this;
    }

    public function getResponsable(): ?string
    {
        return $this->responsable;
    }

    public function setResponsable(string $responsable): static
    {
        $this->responsable = $responsable;

        return $this;
    }

    public function getDateGestion(): ?\DateTimeInterface
    {
        return $this->dateGestion;
    }

    public function setDateGestion(\DateTimeInterface $dateGestion): static
    {
        $this->dateGestion = $dateGestion;

        return $this;
    }

    public function getCommentaire(): ?string
    {
        return $this->commentaire;
    }

    public function setCommentaire(?string $commentaire): static
    {
        $this->commentaire = $commentaire;

        return $this;
    }

    public function getStatut(): ?string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

        return $this;
    }
}
