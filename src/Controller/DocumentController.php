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
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;

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
            return $this->redirectToRoute('event_index');
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
}