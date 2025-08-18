<?php

namespace App\Controller;

use App\Entity\Document;
use App\Form\DocumentType;
use App\Repository\EventRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\Exception\FileException; // Import manquant
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/document')]
class DocumentController extends AbstractController
{
    #[Route('/upload/{id}', name: 'document_upload', methods: ['GET', 'POST'])]
    public function upload(
        Request $request,
        EntityManagerInterface $em,
        EventRepository $eventRepo,
        SluggerInterface $slugger,
        int $id
    ): Response {
        $event = $eventRepo->find($id);
        if (!$event) {
            $this->addFlash('error', 'Événement introuvable');
            return $this->redirectToRoute('event_list');
        }

        $document = new Document();
        $document->setEvent($event);

        $form = $this->createForm(DocumentType::class, $document);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $file = $form->get('file')->getData();
            
            if ($file) {
                $originalFilename = pathinfo($file->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$file->guessExtension();

                try {
                    $file->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );
                    $document->setFilename($newFilename);
                } catch (FileException $e) {
                    $this->addFlash('warning', 'Erreur lors de l\'upload: '.$e->getMessage());
                    return $this->redirectToRoute('document_upload', ['id' => $id]);
                }
            }

            $em->persist($document);
            $em->flush();

            $this->addFlash('success', 'Document uploadé avec succès !');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('document/upload.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/download/{id}', name: 'document_download', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function download(int $id, EntityManagerInterface $em): Response
    {
        $document = $em->getRepository(Document::class)->find($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        // Vérifier que l'utilisateur a accès à l'événement
        $user = $this->getUser();
        $event = $document->getEvent();
        
        // L'organisateur a toujours accès
        if ($event->getOrganizer() === $user) {
            // OK
        }
        // L'administrateur a toujours accès
        elseif (in_array('ROLE_ADMIN', $user->getRoles())) {
            // OK
        }
        // Vérifier si l'utilisateur est un participant
        else {
            $hasAccess = false;
            foreach ($event->getParticipations() as $participation) {
                if ($participation->getUser() === $user) {
                    $hasAccess = true;
                    break;
                }
            }
            
            if (!$hasAccess) {
                throw $this->createAccessDeniedException('Vous n\'avez pas accès à ce document');
            }
        }

        $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();
        
        if (!file_exists($filePath)) {
            throw $this->createNotFoundException('Fichier non trouvé sur le serveur');
        }

        $response = new BinaryFileResponse($filePath);
        $response->setContentDisposition(
            ResponseHeaderBag::DISPOSITION_ATTACHMENT,
            $document->getFileName()
        );

        return $response;
    }

    #[Route('/delete/{id}', name: 'document_delete', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function delete(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $document = $em->getRepository(Document::class)->find($id);
        
        if (!$document) {
            throw $this->createNotFoundException('Document non trouvé');
        }

        $event = $document->getEvent();
        $user = $this->getUser();
        
        // Vérifier les droits de suppression
        if ($event->getOrganizer() !== $user && !in_array('ROLE_ADMIN', $user->getRoles())) {
            throw $this->createAccessDeniedException('Vous n\'avez pas le droit de supprimer ce document');
        }

        // Vérifier le token CSRF pour la sécurité
        if ($this->isCsrfTokenValid('delete'.$document->getId(), $request->request->get('_token'))) {
            // Supprimer le fichier physique
            $filePath = $this->getParameter('documents_directory') . '/' . $document->getFileName();
            if (file_exists($filePath)) {
                unlink($filePath);
            }
            
            // Supprimer l'entrée en base de données
            $em->remove($document);
            $em->flush();

            $this->addFlash('success', 'Document supprimé avec succès !');
        } else {
            $this->addFlash('error', 'Token de sécurité invalide');
        }

        return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
    }
}