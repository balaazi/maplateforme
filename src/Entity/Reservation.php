<?php

namespace App\Entity;

use App\Repository\ReservationRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ReservationRepository::class)]
class Reservation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Salle::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Salle $salle = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateDebut = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateFin = null;

    #[ORM\Column(length: 255)]
    private ?string $reservePar = null;

    #[ORM\Column(length: 500, nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(length: 20)]
    private ?string $statut = 'confirmee';

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $dateCreation = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $nombreParticipants = null;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactTelephone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $contactEmail = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $recurrente = false;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $typeRecurrence = null; // hebdomadaire, mensuelle, etc.

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $finRecurrence = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateModification = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modifiePar = null;

    public function __construct()
    {
        $this->dateCreation = new \DateTime();
    }

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

    public function getDateDebut(): ?\DateTimeInterface
    {
        return $this->dateDebut;
    }

    public function setDateDebut(\DateTimeInterface $dateDebut): static
    {
        $this->dateDebut = $dateDebut;

        return $this;
    }

    public function getDateFin(): ?\DateTimeInterface
    {
        return $this->dateFin;
    }

    public function setDateFin(\DateTimeInterface $dateFin): static
    {
        $this->dateFin = $dateFin;

        return $this;
    }

    public function getReservePar(): ?string
    {
        return $this->reservePar;
    }

    public function setReservePar(string $reservePar): static
    {
        $this->reservePar = $reservePar;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

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

    public function getDateCreation(): ?\DateTimeInterface
    {
        return $this->dateCreation;
    }

    public function setDateCreation(\DateTimeInterface $dateCreation): static
    {
        $this->dateCreation = $dateCreation;

        return $this;
    }

    /**
     * Vérifie si cette réservation chevauche avec une autre période
     */
    public function chevaucheAvec(\DateTimeInterface $debut, \DateTimeInterface $fin): bool
    {
        return $this->dateDebut < $fin && $this->dateFin > $debut;
    }

    /**
     * Vérifie si la réservation est active (confirmée et en cours)
     */
    public function estActive(): bool
    {
        $maintenant = new \DateTime();
        return $this->statut === 'confirmee' && 
               $this->dateDebut <= $maintenant && 
               $this->dateFin > $maintenant;
    }

    public function getNombreParticipants(): ?int
    {
        return $this->nombreParticipants;
    }

    public function setNombreParticipants(?int $nombreParticipants): static
    {
        $this->nombreParticipants = $nombreParticipants;
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): static
    {
        $this->notes = $notes;
        return $this;
    }

    public function getContactTelephone(): ?string
    {
        return $this->contactTelephone;
    }

    public function setContactTelephone(?string $contactTelephone): static
    {
        $this->contactTelephone = $contactTelephone;
        return $this;
    }

    public function getContactEmail(): ?string
    {
        return $this->contactEmail;
    }

    public function setContactEmail(?string $contactEmail): static
    {
        $this->contactEmail = $contactEmail;
        return $this;
    }

    public function isRecurrente(): ?bool
    {
        return $this->recurrente;
    }

    public function setRecurrente(bool $recurrente): static
    {
        $this->recurrente = $recurrente;
        return $this;
    }

    public function getTypeRecurrence(): ?string
    {
        return $this->typeRecurrence;
    }

    public function setTypeRecurrence(?string $typeRecurrence): static
    {
        $this->typeRecurrence = $typeRecurrence;
        return $this;
    }

    public function getFinRecurrence(): ?\DateTimeInterface
    {
        return $this->finRecurrence;
    }

    public function setFinRecurrence(?\DateTimeInterface $finRecurrence): static
    {
        $this->finRecurrence = $finRecurrence;
        return $this;
    }

    public function getDateModification(): ?\DateTimeInterface
    {
        return $this->dateModification;
    }

    public function setDateModification(?\DateTimeInterface $dateModification): static
    {
        $this->dateModification = $dateModification;
        return $this;
    }

    public function getModifiePar(): ?string
    {
        return $this->modifiePar;
    }

    public function setModifiePar(?string $modifiePar): static
    {
        $this->modifiePar = $modifiePar;
        return $this;
    }

    public function getStatutLabel(): string
    {
        return match($this->statut) {
            'confirmee' => 'Confirmée',
            'en_attente' => 'En attente',
            'annulee' => 'Annulée',
            'terminee' => 'Terminée',
            default => 'Confirmée'
        };
    }

    public function getDuree(): \DateInterval
    {
        return $this->dateDebut->diff($this->dateFin);
    }

    public function getDureeEnHeures(): float
    {
        $duree = $this->getDuree();
        return $duree->h + ($duree->i / 60);
    }
}
