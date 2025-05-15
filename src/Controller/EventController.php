<?php

namespace App\Controller;

use App\Entity\Event;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use App\Service\EventNotificationService;
use App\Service\GoogleDriveService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\HttpFoundation\File\Exception\FileException;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


class EventController extends AbstractController
{
    private $notificationService;

    public function __construct(EventNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

public function create(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger, GoogleDriveService $driveService, SessionInterface $session): Response
{
    $event = new Event();
    $form = $this->createForm(EventFormType::class, $event);
    $form->handleRequest($request);

    if ($form->isSubmitted() && $form->isValid()) {
        $event->setOrganizer($this->getUser());

        $token = $session->get('google_access_token');
        if ($token) {
            $_SESSION['google_access_token'] = $token;

            $folderId = $driveService->createFolder('Event_' . $event->getTitle());
            if ($folderId) {
                $event->setGoogleDriveUrl('https://drive.google.com/drive/folders/' . $folderId);
            }
        }

        /** @var UploadedFile[] $documents */
        $documents = $form->get('documents')->getData();

        $uploadedFileNames = []; // مصفوفة لتخزين أسماء الملفات

        if ($documents) {
            foreach ($documents as $document) {
                $originalFilename = pathinfo($document->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename . '-' . uniqid() . '.' . $document->guessExtension();

                try {
                    $document->move(
                        $this->getParameter('documents_directory'),
                        $newFilename
                    );
                    $uploadedFileNames[] = $newFilename; // خزّن اسم الملف
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors du téléchargement de ' . $document->getClientOriginalName());
                }
            }
        }

        // خزّن أسماء الملفات في الكائن Event
        $event->setUploadedDocuments($uploadedFileNames);

        $entityManager->persist($event);
        $entityManager->flush();

        $this->addFlash('success', 'Événement créé avec succès !');
        return $this->redirectToRoute('calendar_page');
    }

    return $this->render('event/create.html.twig', [
        'form' => $form->createView(),
    ]);
}




    #[Route('/event/list', name: 'event_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)->findAll();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit')]
    public function edit(Event $event, Request $request, EntityManagerInterface $em): Response
    {
        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();

            $this->notificationService->sendEventUpdateNotification($event);

            $this->addFlash('success', 'Événement modifié avec succès.');

            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/cancel', name: 'event_cancel')]
    public function cancelEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);

        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $event->setStatus('annulé');

        $em->flush();

        $this->notificationService->sendEventCancelNotification($event);  // Appel de la méthode d'annulation

        $this->addFlash('success', 'Événement annulé avec succès.');
        return $this->redirectToRoute('event_list');
    }

    #[Route('/event', name: 'event_index')]
    public function index(EventRepository $eventRepository): Response
    {
        $events = $eventRepository->findAll();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }
    #[Route('/calendar', name: 'calendar_page')]
    public function calendar(): Response
    {
    return $this->render('calendar/index.html.twig');
    }

    public function showEvent(Event $event)
    {
    return $this->render('event/show.html.twig', [
    'event' => $event,
    'googleDriveUrl' => 'https://drive.google.com/embeddedfolderview?id=ID_DOSSIER_GOOGLE',
    'notionUrl' => 'https://www.notion.so/LIEN_DE_TA_PAGE_NOTION',
    'etherpadUrl' => 'https://etherpad.domain.tld/p/ID_PAD_EVENT',
    ]);
    }
 #[Route('/event/{id}/fichiers', name: 'event_files')]
public function listFiles(Event $event, Request $request, GoogleDriveService $driveService): Response
{
    $token = $request->getSession()->get('google_access_token');

    if (!$token) {
        return $this->redirectToRoute('google_drive_connect');
    }

    $client = $driveService->getClient();
    $client->setAccessToken($token);

    if ($client->isAccessTokenExpired()) {
        return $this->redirectToRoute('google_drive_connect');
    }

    $drive = $driveService->getDriveService();

    preg_match('/\/folders\/([^\/\?]+)/', $event->getGoogleDriveUrl(), $matches);
    $folderId = $matches[1] ?? null;

    if (!$folderId) {
        return new Response("Lien du dossier Google Drive invalide.");
    }

    $files = $drive->files->listFiles([
        'q' => "'$folderId' in parents and trashed = false",
        'fields' => 'files(id, name, mimeType, webViewLink)'
    ]);

    return $this->render('event/files.html.twig', [
        'event' => $event,
        'files' => $files->getFiles(),
    ]);
}


}
