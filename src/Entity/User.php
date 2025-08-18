<?php
// src/Entity/User.php

namespace App\Entity;

use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Bridge\Doctrine\Validator\Constraints\UniqueEntity;
use App\Repository\UserRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
#[UniqueEntity(fields: ['email'], message: 'Cette adresse email est déjà utilisée.')]
class User implements UserInterface, PasswordAuthenticatedUserInterface
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private ?string $email = null;

    #[ORM\Column(type: Types::STRING, length: 255)]
    private ?string $password = null;

    #[ORM\Column(type: Types::JSON)]
    private array $roles = [];

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $googleToken = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $nom = null;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private ?string $prenom = null;

    #[ORM\Column(type: Types::DATE_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $dateNaissance = null;

    #[ORM\ManyToOne(targetEntity: Departement::class, inversedBy: 'users')]
    #[ORM\JoinColumn(nullable: true)]
    private ?Departement $departement = null;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $notifyByEmail = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => false])]
    private bool $notifyBySms = false;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enableSoundNotifications = true;

    #[ORM\Column(type: Types::BOOLEAN, options: ['default' => true])]
    private bool $enableVisualNotifications = true;

    #[ORM\Column(type: Types::INTEGER, options: ['default' => 1])]
    private int $reminderFrequency = 1;

    #[ORM\Column(type: Types::STRING, length: 20, options: ['default' => 'normal'])]
    private string $notificationPriority = 'normal';

    #[ORM\Column(type: Types::STRING, length: 255, nullable: true)]
    private ?string $photo = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $societe = null;

    #[ORM\Column(type: Types::STRING, length: 100, nullable: true)]
    private ?string $specialite = null;

    #[ORM\Column(type: Types::STRING, length: 20, nullable: true)]
    private ?string $telephone = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE, nullable: true)]
    private ?\DateTimeInterface $updatedAt = null;

    #[ORM\Column(type: Types::DATETIME_MUTABLE)]
    private ?\DateTimeInterface $createdAt = null;

    #[ORM\OneToMany(mappedBy: 'user', targetEntity: Participation::class, cascade: ['remove'])]
    private Collection $participations;

    public function __construct()
    {
        $this->participations = new ArrayCollection();
        $this->createdAt = new \DateTime();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(string $email): static
    {
        $this->email = $email;
        return $this;
    }

    public function getPassword(): ?string
    {
        return $this->password;
    }

    public function setPassword(string $password): static
    {
        $this->password = $password;
        return $this;
    }

    public function getRoles(): array
    {
        $roles = $this->roles;
       /// $roles[] = 'ROLE_USER'; // Garantit que tout utilisateur a au moins ROLE_USER
        return array_unique($roles);
    }

    public function setRoles(array $roles): static
    {
        $this->roles = $roles;
        return $this;
    }

    public function getGoogleToken(): ?string
    {
        return $this->googleToken;
    }

    public function setGoogleToken(?string $googleToken): static
    {
        $this->googleToken = $googleToken;
        return $this;
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

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(string $prenom): static
    {
        $this->prenom = $prenom;
        return $this;
    }

    public function getDateNaissance(): ?\DateTimeInterface
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeInterface $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;
        return $this;
    }

    public function getDepartement(): ?Departement
    {
        return $this->departement;
    }

    public function setDepartement(?Departement $departement): static
    {
        $this->departement = $departement;
        return $this;
    }

    public function isNotifyByEmail(): bool
    {
        return $this->notifyByEmail;
    }

    public function setNotifyByEmail(bool $notifyByEmail): static
    {
        $this->notifyByEmail = $notifyByEmail;
        return $this;
    }

    public function isNotifyBySms(): bool
    {
        return $this->notifyBySms;
    }

    public function setNotifyBySms(bool $notifyBySms): static
    {
        $this->notifyBySms = $notifyBySms;
        return $this;
    }

    public function isEnableSoundNotifications(): bool
    {
        return $this->enableSoundNotifications;
    }

    public function setEnableSoundNotifications(bool $enableSoundNotifications): static
    {
        $this->enableSoundNotifications = $enableSoundNotifications;
        return $this;
    }

    public function isEnableVisualNotifications(): bool
    {
        return $this->enableVisualNotifications;
    }

    public function setEnableVisualNotifications(bool $enableVisualNotifications): static
    {
        $this->enableVisualNotifications = $enableVisualNotifications;
        return $this;
    }

    public function getReminderFrequency(): int
    {
        return $this->reminderFrequency;
    }

    public function setReminderFrequency(int $reminderFrequency): static
    {
        $this->reminderFrequency = $reminderFrequency;
        return $this;
    }

    public function getNotificationPriority(): string
    {
        return $this->notificationPriority;
    }

    public function setNotificationPriority(string $notificationPriority): static
    {
        $this->notificationPriority = $notificationPriority;
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

    public function getSociete(): ?string
    {
        return $this->societe;
    }

    public function setSociete(?string $societe): static
    {
        $this->societe = $societe;
        return $this;
    }

    public function getSpecialite(): ?string
    {
        return $this->specialite;
    }

    public function setSpecialite(?string $specialite): static
    {
        $this->specialite = $specialite;
        return $this;
    }

    public function getTelephone(): ?string
    {
        return $this->telephone;
    }

    public function setTelephone(?string $telephone): static
    {
        $this->telephone = $telephone;
        return $this;
    }

    public function getUpdatedAt(): ?\DateTimeInterface
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(\DateTimeInterface $updatedAt): static
    {
        $this->updatedAt = $updatedAt;
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

    // Méthodes requises par UserInterface
    public function eraseCredentials(): void
    {
        // Nettoyage des données sensibles temporaires
    }

    public function getUserIdentifier(): string
    {
        return (string) $this->email;
    }

    // Méthode pour obtenir le nom complet
    public function getFullName(): string
    {
        return $this->prenom . ' ' . $this->nom;
    }

    public function __toString(): string
    {
        return $this->getFullName() . ' (' . $this->email . ')';
    }

    /**
     * @return Collection<int, Participation>
     */
    public function getParticipations(): Collection
    {
        return $this->participations;
    }

    public function addParticipation(Participation $participation): static
    {
        if (!$this->participations->contains($participation)) {
            $this->participations->add($participation);
            $participation->setUser($this);
        }
        return $this;
    }

    public function removeParticipation(Participation $participation): static
    {
        if ($this->participations->removeElement($participation)) {
            // set the owning side to null (unless already changed)
            if ($participation->getUser() === $this) {
                $participation->setUser(null);
            }
        }
        return $this;
    }
}