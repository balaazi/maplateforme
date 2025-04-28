<?php
namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

#[ORM\Entity]
class Event
{
#[ORM\Id]
#[ORM\GeneratedValue]
#[ORM\Column(type: 'integer')]
private ?int $id = null;

#[ORM\Column(type: 'string', length: 255)]
private ?string $title = null;

#[ORM\Column(type: 'text', nullable: true)]
private ?string $description = null;

#[ORM\Column(type: 'string', length: 255)]
private ?string $lieu = null;

#[ORM\Column(type: 'datetime')]
private ?\DateTimeInterface $dateHeure = null;

#[ORM\Column(type: 'integer')]
private ?int $duree = null;

#[ORM\Column(type: 'string', length: 50)]
private ?string $category = null;

#[ORM\OneToMany(mappedBy: 'event', targetEntity: Invitation::class, cascade: ['persist', 'remove'])]
private Collection $invitations;

#[ORM\Column(type: 'string', length: 255, nullable: true)]
private ?string $status = null;

#[ORM\ManyToOne(targetEntity: User::class)]
#[ORM\JoinColumn(name: 'organizer_id', referencedColumnName: 'id', nullable: false)]
private ?User $organizer = null;

#[ORM\ManyToMany(targetEntity: User::class, inversedBy: 'events')]
#[ORM\JoinTable(name: 'event_user')] // Cette ligne définit la table de jointure
private Collection $participants;

public function __construct()
{
// Initialisation de la collection pour les invitations
$this->invitations = new ArrayCollection();
}

public function getId(): ?int
{
return $this->id;
}

public function getTitle(): ?string
{
return $this->title;
}

public function setTitle(string $title): self
{
$this->title = $title;
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

public function getLieu(): ?string
{
return $this->lieu;
}

public function setLieu(string $lieu): self
{
$this->lieu = $lieu;
return $this;
}

public function getDateHeure(): ?\DateTimeInterface
{
return $this->dateHeure;
}

public function setDateHeure(\DateTimeInterface $dateHeure): self
{
$this->dateHeure = $dateHeure;
return $this;
}

public function getDuree(): ?int
{
return $this->duree;
}

public function setDuree(int $duree): self
{
$this->duree = $duree;
return $this;
}

public function getCategory(): ?string
{
return $this->category;
}

public function setCategory(string $category): self
{
$this->category = $category;
return $this;
}

public function getStatus(): ?string
{
return $this->status;
}

public function setStatus(?string $status): self
{
$this->status = $status;
return $this;
}

public function getOrganizer(): ?User
{
return $this->organizer;
}

public function setOrganizer(?User $organizer): self
{
$this->organizer = $organizer;
return $this;
}

public function getInvitations(): Collection
{
return $this->invitations;
}

public function addInvitation(Invitation $invitation): self
{
if (!$this->invitations->contains($invitation)) {
$this->invitations[] = $invitation;
$invitation->setEvent($this);
}

return $this;
}

public function removeInvitation(Invitation $invitation): self
{
if ($this->invitations->contains($invitation)) {
$this->invitations->removeElement($invitation);
if ($invitation->getEvent() === $this) {
$invitation->setEvent(null);
}
}

return $this;
}
public function getParticipants(): Collection
{
    return $this->participants;
}
public function addParticipant(User $user): self
{
    if (!$this->participants->contains($user)) {
        $this->participants[] = $user;
    }

    return $this;
}

public function removeParticipant(User $user): self
{
    $this->participants->removeElement($user);

    return $this;
}
}
