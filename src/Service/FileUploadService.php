<?php

namespace App\Service;

use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\String\Slugger\SluggerInterface;

class FileUploadService
{
    private string $documentsDirectory;
    private SluggerInterface $slugger;

    public function __construct(string $documentsDirectory, SluggerInterface $slugger)
    {
        $this->documentsDirectory = $documentsDirectory;
        $this->slugger = $slugger;
    }

    public function upload(UploadedFile $file): string
    {
        // Vérifier que le dossier existe et est accessible en écriture
        if (!is_dir($this->documentsDirectory)) {
            if (!mkdir($this->documentsDirectory, 0777, true)) {
                throw new FileException('Impossible de créer le dossier de destination.');
            }
        }

        if (!is_writable($this->documentsDirectory)) {
            throw new FileException('Le dossier de destination n\'est pas accessible en écriture.');
        }

        $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
        $safeFilename = $this->slugger->slug($originalFilename);
        $fileName = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

        try {
            $file->move($this->documentsDirectory, $fileName);
        } catch (FileException $e) {
            throw new FileException('Erreur lors du téléchargement du fichier: ' . $e->getMessage());
        }

        return $fileName;
    }

    public function getDocumentsDirectory(): string
    {
        return $this->documentsDirectory;
    }
} 