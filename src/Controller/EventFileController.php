<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\EventFile;
use App\Form\EventFileType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class EventFileController extends AbstractController
{
    #[Route('/event/{id}/upload', name: 'event_upload_file')]
    public function upload(
        Event $event,
        Request $request,
        EntityManagerInterface $em
    ): Response {
        $form = $this->createForm(EventFileType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $uploadedFile */
            $uploadedFile = $form->get('file')->getData();

            $newFileName = uniqid() . '-' . $uploadedFile->getClientOriginalName();
            $uploadDir = $this->getParameter('photos_directory');

            $uploadedFile->move($uploadDir, $newFileName);

            $eventFile = new EventFile();
            $eventFile->setName($newFileName);
            $eventFile->setGoogleDriveFileId(''); // À gérer si synchronisation Drive
            $eventFile->setEvent($event);

            $em->persist($eventFile);
            $em->flush();

            $this->addFlash('success', '📁 Fichier uploadé avec succès.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        return $this->render('event_file/upload.html.twig', [
            'event' => $event,
            'form' => $form->createView(),
        ]);
    }
}
