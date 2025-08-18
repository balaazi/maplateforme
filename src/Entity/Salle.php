<?php

namespace App\Entity;

use App\Repository\SalleRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SalleRepository::class)]
class Salle
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $debutReservation = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $finReservation = null;

    #[ORM\Column(type: Types::INTEGER)]
    private ?int $capacite = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $disponible = true;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    #[ORM\Column(length: 100, nullable: true)]
    private ?string $localisation = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $equipements = null;

    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $horairesParJour = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $type = 'reunion'; // reunion, conference, formation, etc.

    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2, nullable: true)]
    private ?string $superficie = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::BOOLEAN)]
    private ?bool $accessibilite = false;

    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $tarif = null;

    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $priorite = 1; // 1=basse, 2=normale, 3=haute

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getDebutReservation(): ?\DateTimeInterface
    {
        return $this->debutReservation;
    }

    public function setDebutReservation(\DateTimeInterface $debutReservation): static
    {
        $this->debutReservation = $debutReservation;

        return $this;
    }

    public function getFinReservation(): ?\DateTimeInterface
    {
        return $this->finReservation;
    }

    public function setFinReservation(\DateTimeInterface $finReservation): static
    {
        $this->finReservation = $finReservation;

        return $this;
    }

    public function getCapacite(): ?int
    {
        return $this->capacite;
    }

    public function setCapacite(int $capacite): static
    {
        $this->capacite = $capacite;

        return $this;
    }

    public function isDisponible(): ?bool
    {
        return $this->disponible;
    }

    public function setDisponible(bool $disponible): static
    {
        $this->disponible = $disponible;

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

    public function getLocalisation(): ?string
    {
        return $this->localisation;
    }

    public function setLocalisation(?string $localisation): static
    {
        $this->localisation = $localisation;
        return $this;
    }

    public function getEquipements(): ?array
    {
        return $this->equipements ?? [];
    }

    public function setEquipements(?array $equipements): static
    {
        $this->equipements = $equipements;
        return $this;
    }

    public function addEquipement(string $equipement): static
    {
        $equipements = $this->getEquipements();
        if (!in_array($equipement, $equipements)) {
            $equipements[] = $equipement;
            $this->setEquipements($equipements);
        }
        return $this;
    }

    public function removeEquipement(string $equipement): static
    {
        $equipements = $this->getEquipements();
        $key = array_search($equipement, $equipements);
        if ($key !== false) {
            unset($equipements[$key]);
            $this->setEquipements(array_values($equipements));
        }
        return $this;
    }

    public function hasEquipement(string $equipement): bool
    {
        return in_array($equipement, $this->getEquipements());
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): static
    {
        $this->type = $type;
        return $this;
    }

    public function getSuperficie(): ?string
    {
        return $this->superficie;
    }

    public function setSuperficie(?string $superficie): static
    {
        $this->superficie = $superficie;
        return $this;
    }

    public function getPhoto(): ?string
    {
        return $this->photo;
    }

    public function setPhoto(?string $photo): static
    {
        $this->photo = $photo;
        return $this;
    }

    public function isAccessibilite(): ?bool
    {
        return $this->accessibilite;
    }

    public function setAccessibilite(bool $accessibilite): static
    {
        $this->accessibilite = $accessibilite;
        return $this;
    }

    public function getTarif(): ?string
    {
        return $this->tarif;
    }

    public function setTarif(?string $tarif): static
    {
        $this->tarif = $tarif;
        return $this;
    }

    public function getPriorite(): ?int
    {
        return $this->priorite;
    }

    public function setPriorite(?int $priorite): static
    {
        $this->priorite = $priorite;
        return $this;
    }

    public function getPrioriteLabel(): string
    {
        return match($this->priorite) {
            1 => 'Basse',
            2 => 'Normale', 
            3 => 'Haute',
            default => 'Normale'
        };
    }

    public function getTypeLabel(): string
    {
        return match($this->type) {
            'reunion' => 'Salle de réunion',
            'formation' => 'Salle de formation',
            'bureau' => 'Bureau',
            'amphitheatre' => 'Amphithéâtre',
            default => 'Salle de réunion'
        };
    }

    public function getHorairesParJour(): ?array
    {
        return $this->horairesParJour;
    }

    public function setHorairesParJour(?array $horairesParJour): static
    {
        $this->horairesParJour = $horairesParJour;
        return $this;
    }

    public function __toString(): string
    {
        return $this->nom ?? '';
    }
}
