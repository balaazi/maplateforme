<?php

namespace App\Entity;

use Doctrine\ORM\Mapping as ORM;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Symfony\Component\HttpFoundation\File\File;
use Vich\UploaderBundle\Mapping\Annotation as Vich;

#[ORM\Entity]
#[Vich\Uploadable]
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
    private ?\DateTime $dateHeure = null;

    #[ORM\Column(type: 'integer')]
    private ?int $duree = null;

    #[ORM\Column(type: 'string', length: 50)]
    private ?string $category = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $status = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?User $organizer = null;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Invitation::class, cascade: ['persist', 'remove'])]
    private Collection $invitations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Document::class)]
    private Collection $documents;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $googleDriveUrl = null;

    #[ORM\Column(nullable: true)]
    private ?string $googleDriveFolderId = null;

    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $etherpadUrl = null;

    #[ORM\Column(type: 'json', nullable: true)]
    private ?array $uploadedDocuments = [];

    #[Vich\UploadableField(mapping: 'documents', fileNameProperty: 'fileName')]
    private ?File $imageFile = null;

    #[ORM\Column(nullable: true)]
    private ?string $fileName = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $updatedAt = null;
    #[ORM\OneToMany(mappedBy: 'event', targetEntity: Participation::class, orphanRemoval: true)]
    private Collection $participations;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: EventFile::class, cascade: ['persist', 'remove'])]
    private Collection $files;

    #[ORM\OneToMany(mappedBy: 'event', targetEntity: CollaborativeNote::class, cascade: ['persist', 'remove'])]
    private Collection $collaborativeNotes;

    public function __construct()
    {
        $this->invitations = new ArrayCollection();
        $this->documents = new ArrayCollection(); 
        $this->participations = new ArrayCollection();
        $this->files = new ArrayCollection();
        $this->collaborativeNotes = new ArrayCollection();
    }

    /**
     * If manually uploading a file (i.e. not using Symfony Form) ensure an instance
     * of 'UploadedFile' is injected into this setter to trigger the update. If this
     * bundle's configuration parameter 'inject_on_load' is set to 'true' this setter
     * must be able to accept an instance of 'File' as the bundle will inject one here
     * during Doctrine hydration.
     *
     * @param File|\Symfony\Component\HttpFoundation\File\UploadedFile|null $imageFile
     */
    public function setImageFile(?File $imageFile = null): void
    {
        $this->imageFile = $imageFile;

        if (null !== $imageFile) {
            // It is required that at least one field changes if you are using doctrine
            // otherwise the event listeners won't be called and the file is lost
            $this->updatedAt = new \DateTimeImmutable();
        }
    }

    public function getImageFile(): ?File
    {
        return $this->imageFile;
    }
    public function getId(): ?int { return $this->id; }
    public function getTitle(): ?string { return $this->title; }
    public function setTitle(string $title): self { $this->title = $title; return $this; }
    public function getDescription(): ?string { return $this->description; }
    public function setDescription(?string $description): self { $this->description = $description; return $this; }
    public function getLieu(): ?string { return $this->lieu; }
    public function setLieu(string $lieu): self { $this->lieu = $lieu; return $this; }
    public function getDateHeure(): ?\DateTime 
    { 
        if ($this->dateHeure === null) {
            return null;
        }
        
        // Retourner une copie de la date dans le fuseau horaire de Paris
        $date = clone $this->dateHeure;
        $date->setTimezone(new \DateTimeZone('Europe/Paris'));
        return $date;
    }
    public function setDateHeure(?\DateTimeInterface $dateHeure): self 
    { 
        if ($dateHeure === null) {
            $this->dateHeure = null;
            return $this;
        }

        // Créer une nouvelle instance DateTime dans le fuseau horaire de Paris
        $this->dateHeure = new \DateTime(
            $dateHeure->format('Y-m-d H:i:s'),
            new \DateTimeZone('Europe/Paris')
        );
        
        return $this; 
    }
    public function getDuree(): ?int { return $this->duree; }
    public function setDuree(int $duree): self { $this->duree = $duree; return $this; }
    public function getCategory(): ?string { return $this->category; }
    public function setCategory(string $category): self { $this->category = $category; return $this; }
    public function getStatus(): ?string { return $this->status; }
    public function setStatus(?string $status): self { $this->status = $status; return $this; }
    public function getOrganizer(): ?User { return $this->organizer; }
    public function setOrganizer(?User $organizer): self { $this->organizer = $organizer; return $this; }

    public function getGoogleDriveFolderId(): ?string { return $this->googleDriveFolderId; }
    public function setGoogleDriveFolderId(?string $googleDriveFolderId): self { $this->googleDriveFolderId = $googleDriveFolderId; return $this; }
    public function getGoogleDriveUrl(): ?string { return $this->googleDriveUrl; }
    public function setGoogleDriveUrl(?string $googleDriveUrl): self { $this->googleDriveUrl = $googleDriveUrl; return $this; }

    public function getEtherpadUrl(): ?string { return $this->etherpadUrl; }
    public function setEtherpadUrl(?string $etherpadUrl): self { $this->etherpadUrl = $etherpadUrl; return $this; }

    public function getFileName(): ?string
    {
        return $this->fileName;
    }

    public function setFileName(?string $fileName): void
    {
        $this->fileName = $fileName;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(?\DateTimeImmutable $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    public function getInvitations(): Collection { return $this->invitations; }
    public function addInvitation(Invitation $invitation): self {
        if (!$this->invitations->contains($invitation)) {
            $this->invitations[] = $invitation;
            $invitation->setEvent($this);
        }
        return $this;
    }
    public function removeInvitation(Invitation $invitation): self {
        if ($this->invitations->removeElement($invitation)) {
            if ($invitation->getEvent() === $this) {
                $invitation->setEvent(null);
            }
        }
        return $this;
    }
    public function getDocuments(): Collection { return $this->documents; }
    public function addDocument(Document $document): self {
        if (!$this->documents->contains($document)) {
            $this->documents[] = $document;
            $document->setEvent($this);
        }
        return $this;
    }
    public function removeDocument(Document $document): self {
        if ($this->documents->removeElement($document)) {
            if ($document->getEvent() === $this) {
                $document->setEvent(null);
            }
        }
        return $this;
    }
    public function getUploadedDocuments(): ?array
    {
    return $this->uploadedDocuments;
    }

    public function setUploadedDocuments(?array $uploadedDocuments): self
    {
    $this->uploadedDocuments = $uploadedDocuments;

    return $this;
}

public function getFiles(): Collection
{
    return $this->files;
}

public function addFile(EventFile $file): self
{
    if (!$this->files->contains($file)) {
        $this->files[] = $file;
        $file->setEvent($this);
    }
    return $this;
}

public function removeFile(EventFile $file): self
{
    if ($this->files->removeElement($file)) {
        if ($file->getEvent() === $this) {
            $file->setEvent(null);
        }
    }
    return $this;
}

public function getCollaborativeNotes(): Collection
{
    return $this->collaborativeNotes;
}

public function addCollaborativeNote(CollaborativeNote $note): self
{
    if (!$this->collaborativeNotes->contains($note)) {
        $this->collaborativeNotes[] = $note;
        $note->setEvent($this);
    }
    return $this;
}

public function removeCollaborativeNote(CollaborativeNote $note): self
{
    if ($this->collaborativeNotes->removeElement($note)) {
        if ($note->getEvent() === $this) {
            $note->setEvent(null);
        }
    }
    return $this;
}

public function getParticipations(): Collection
{
    return $this->participations;
}

public function addParticipation(Participation $participation): self
{
    if (!$this->participations->contains($participation)) {
        $this->participations[] = $participation;
        $participation->setEvent($this);
    }
    return $this;
}

public function removeParticipation(Participation $participation): self
{
    if ($this->participations->removeElement($participation)) {
        if ($participation->getEvent() === $this) {
            $participation->setEvent(null);
        }
    }
    return $this;
}
}
