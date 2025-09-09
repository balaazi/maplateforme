<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\CalendarEvent;
use App\Entity\Invitation;
use App\Entity\Participation;
use App\Entity\User;
use App\Entity\Document;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use App\Repository\ParticipationRepository;
use App\Service\EventNotificationService;
use App\Service\AdminNotificationService;
use App\Service\NotificationService;
use App\Service\GlobalNotificationService;
use App\Service\ReminderService;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use App\Entity\Salle;
use App\Service\SalleDisponibiliteService;
use App\Service\AutoArchiveService;
use App\Enum\InvitationStatus;

class EventController extends AbstractController
{
    private EventNotificationService $eventNotificationService;
    private AdminNotificationService $adminNotificationService;
    private NotificationService $notificationService;
    private GlobalNotificationService $globalNotificationService;
    private ReminderService $reminderService;
    private AutoArchiveService $autoArchiveService;

    public function __construct(
        EventNotificationService $eventNotificationService,
        AdminNotificationService $adminNotificationService,
        NotificationService $notificationService,
        GlobalNotificationService $globalNotificationService,
        ReminderService $reminderService,
        AutoArchiveService $autoArchiveService
    ) {
        $this->eventNotificationService = $eventNotificationService;
        $this->adminNotificationService = $adminNotificationService;
        $this->notificationService = $notificationService;
        $this->globalNotificationService = $globalNotificationService;
        $this->reminderService = $reminderService;
        $this->autoArchiveService = $autoArchiveService;
    }

    #[Route('/event/create', name: 'event_create')]
    #[IsGranted('ROLE_PARTICIPANT')]
    public function create(
        Request $request,
        EntityManagerInterface $entityManager,
        SluggerInterface $slugger,
        GoogleCalendarService $calendarService,
        SessionInterface $session
    ): Response {
        $event = new Event();
        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // DEBUG : Log de début de traitement
            error_log("DEBUG: Création d'événement - Formulaire soumis et valide");
            
            $event->setOrganizer($this->getUser());
            $event->setCreatedBy($this->getUser()); // Ajout important

            $calendarEvent = new CalendarEvent();
            $calendarEvent->setTitle($event->getTitle());
            $calendarEvent->setDescription($event->getDescription());
            $calendarEvent->setStart($event->getDateHeure());

            $end = (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes');
            $calendarEvent->setEnd($end);

            // Traitement des fichiers uploadés
            $uploadedFiles = $form->get('imageFile')->getData();
            error_log("DEBUG: Création - Fichiers uploadés récupérés : " . ($uploadedFiles ? (is_array($uploadedFiles) ? count($uploadedFiles) : '1 fichier unique') : 'Aucun fichier'));
            
            $documentsCreated = 0;
            if ($uploadedFiles) {
                // Vérifier si c'est un fichier unique ou multiple
                if (!is_array($uploadedFiles)) {
                    $uploadedFiles = [$uploadedFiles];
                    error_log("DEBUG: Création - Fichier unique converti en tableau");
                }
                
                error_log("DEBUG: Création - Traitement de " . count($uploadedFiles) . " fichier(s)");
                
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    error_log("DEBUG: Création - Traitement du fichier " . ($index + 1) . " : " . $uploadedFile->getClientOriginalName());
                    
                    if ($uploadedFile) {
                        try {
                            $document = new Document();
                            error_log("DEBUG: Création - Nouvelle entité Document créée");
                            
                            $document->setFile($uploadedFile); // VichUploader gère automatiquement le fileName
                            error_log("DEBUG: Création - Fichier assigné au document");
                            
                            $document->setEvent($event);
                            $event->addDocument($document); // Maintenir la relation bidirectionnelle
                            error_log("DEBUG: Création - Événement assigné au document et document ajouté à l'événement");
                            
                            $entityManager->persist($document);
                            error_log("DEBUG: Création - Document persisté en EntityManager");
                            
                            $documentsCreated++;
                            error_log("DEBUG: Création - Document " . $documentsCreated . " créé avec succès");
                        } catch (\Exception $e) {
                            error_log('ERROR: Création - Erreur lors de la création du document: ' . $e->getMessage());
                            error_log('ERROR: Création - Trace: ' . $e->getTraceAsString());
                        }
                    }
                }
            } else {
                error_log("DEBUG: Création - Aucun fichier uploadé détecté");
            }
            
            error_log("DEBUG: Création - Total documents créés : " . $documentsCreated);
            
            $entityManager->persist($event);
            $entityManager->persist($calendarEvent);
            $entityManager->flush();

            // ✅ NOUVEAUTÉ : Créer automatiquement des rappels pour l'événement
            try {
                $reminders = $this->reminderService->createReminderSchedule($event, [1440, 60, 15]); // 24h, 1h, 15min avant
                $successMessage = sprintf('Événement créé avec succès ! %d rappel(s) automatique(s) programmé(s).', count($reminders));
                if ($documentsCreated > 0) {
                    $successMessage .= sprintf(' %d document(s) uploadé(s).', $documentsCreated);
                }
                $this->addFlash('success', $successMessage);
            } catch (\Exception $e) {
                error_log('Erreur création rappels automatiques: ' . $e->getMessage());
                $warningMessage = 'Événement créé mais problème avec les rappels automatiques. Veuillez vérifier vos préférences de notification.';
                if ($documentsCreated > 0) {
                    $warningMessage .= sprintf(' %d document(s) uploadé(s) avec succès.', $documentsCreated);
                }
                $this->addFlash('warning', $warningMessage);
            }

            // Créer une notification pour l'organisateur
            try {
                $this->notificationService->createNotification(
                    $this->getUser(),
                    "Événement créé avec succès",
                    "Votre événement '{$event->getTitle()}' a été créé et programmé pour le {$event->getDateHeure()->format('d/m/Y à H:i')}.",
                    'event_reminder',
                    $event
                );
            } catch (\Exception $e) {
                error_log('Erreur création notification utilisateur: ' . $e->getMessage());
            }

            // Notification administrateur pour la création
            try {
                $this->adminNotificationService->notifyEventCreated($event);
            } catch (\Exception $e) {
                // Log l'erreur mais ne pas empêcher la création de l'événement
                error_log('Erreur notification admin création événement: ' . $e->getMessage());
            }

            // Notification globale pour la création
            try {
                $this->globalNotificationService->notifyPlatformModification('créé', 'event', $event);
            } catch (\Exception $e) {
                error_log('Erreur notification globale création événement: ' . $e->getMessage());
            }

            try {
                if (!$calendarService->isAuthenticated()) {
                    $session->set('intended_route', 'event_create');
                    return $this->redirectToRoute('google_calendar_connect');
                }

                $calendarService->exportToGoogleCalendar($calendarEvent);
                $calendarService->synchronizeCalendars();

                $this->addFlash('success', 'Événement créé et synchronisé dans les deux sens avec Google Calendar.');
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Invalid token format') || 
                    str_contains($e->getMessage(), 'Token has expired')) {
                    $session->set('intended_route', 'event_create');
                    return $this->redirectToRoute('google_calendar_connect');
                }

                if (str_contains($e->getMessage(), 'EntityManager is closed')) {
                    $this->addFlash('warning', 'Événement créé mais problème de synchronisation : Erreur de base de données. Veuillez réessayer la synchronisation plus tard.');
                } elseif (str_contains($e->getMessage(), 'cascade persist')) {
                    $this->addFlash('warning', 'Événement créé mais problème de synchronisation : Erreur de persistance des données. Veuillez réessayer la synchronisation plus tard.');
                } elseif (str_contains($e->getMessage(), 'Integrity constraint violation') || str_contains($e->getMessage(), 'cannot be null')) {
                    $this->addFlash('warning', 'Événement créé mais problème de synchronisation : Données manquantes. Veuillez réessayer la synchronisation plus tard.');
                } else {
                $this->addFlash('warning', 'Événement créé mais problème de synchronisation : ' . $e->getMessage());
                }
            }

            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/list', name: 'event_list')]
    #[IsGranted('ROLE_USER')]
    public function list(EventRepository $eventRepository, Request $request): Response
    {
        $user = $this->getUser();
        $showArchives = $request->query->get('archives') === '1';
        
        // Archivage automatique en temps réel des événements terminés
        if (!$showArchives) {
            $archivedCount = $this->autoArchiveService->checkAndArchiveCompletedEvents();
            if ($archivedCount > 0) {
                $this->addFlash('info', sprintf('%d événement(s) terminé(s) archivé(s) automatiquement.', $archivedCount));
            }
        }
        
        // Utiliser la méthode du repository qui filtre correctement les événements annulés
        if ($showArchives) {
            $events = $eventRepository->findArchivedEventsForUser($user);
        } else {
            $events = $eventRepository->findEventsForUser($user);
        }

        return $this->render('event/list.html.twig', [
            'events' => $events,
            'showArchives' => $showArchives,
            'archivedCount' => $eventRepository->count(['archive' => true, 'createdBy' => $user]),
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé. Vous ne pouvez modifier que vos propres événements.');
        }

        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // DEBUG : Log de début de traitement
            error_log("DEBUG: Formulaire soumis et valide pour l'événement ID " . $event->getId());
            
            // Traitement des fichiers uploadés lors de la modification
            $uploadedFiles = $form->get('imageFile')->getData();
            error_log("DEBUG: Fichiers uploadés récupérés : " . ($uploadedFiles ? (is_array($uploadedFiles) ? count($uploadedFiles) : '1 fichier unique') : 'Aucun fichier'));
            
            $documentsCreated = 0;
            if ($uploadedFiles) {
                // Vérifier si c'est un fichier unique ou multiple
                if (!is_array($uploadedFiles)) {
                    $uploadedFiles = [$uploadedFiles];
                    error_log("DEBUG: Fichier unique converti en tableau");
                }
                
                error_log("DEBUG: Traitement de " . count($uploadedFiles) . " fichier(s)");
                
                foreach ($uploadedFiles as $index => $uploadedFile) {
                    error_log("DEBUG: Traitement du fichier " . ($index + 1) . " : " . $uploadedFile->getClientOriginalName());
                    
                    if ($uploadedFile) {
                        try {
                            $document = new Document();
                            error_log("DEBUG: Nouvelle entité Document créée");
                            
                            $document->setFile($uploadedFile); // VichUploader gère automatiquement le fileName
                            error_log("DEBUG: Fichier assigné au document");
                            
                            $document->setEvent($event);
                            $event->addDocument($document); // Maintenir la relation bidirectionnelle
                            error_log("DEBUG: Événement assigné au document et document ajouté à l'événement");
                            
                            $em->persist($document);
                            error_log("DEBUG: Document persisté en EntityManager");
                            
                            $documentsCreated++;
                            error_log("DEBUG: Document " . $documentsCreated . " créé avec succès");
                        } catch (\Exception $e) {
                            error_log('ERROR: Erreur lors de la création du document: ' . $e->getMessage());
                            error_log('ERROR: Trace: ' . $e->getTraceAsString());
                        }
                    }
                }
            } else {
                error_log("DEBUG: Aucun fichier uploadé détecté");
            }
            
            error_log("DEBUG: Total documents créés : " . $documentsCreated);
            
            $em->flush();
            error_log("DEBUG: EntityManager flush effectué");
            
            // Notification aux participants
            $this->eventNotificationService->sendEventUpdateNotification($event);
            
            // Notification administrateur pour la modification
            try {
                $this->adminNotificationService->notifyEventUpdated($event);
            } catch (\Exception $e) {
                error_log('Erreur notification admin modification événement: ' . $e->getMessage());
            }

            // Notification globale pour la modification
            try {
                $this->globalNotificationService->notifyPlatformModification('modifié', 'event', $event);
            } catch (\Exception $e) {
                error_log('Erreur notification globale modification événement: ' . $e->getMessage());
            }
            
            $successMessage = 'Événement modifié avec succès.';
            if ($documentsCreated > 0) {
                $successMessage .= sprintf(' %d document(s) supplémentaire(s) uploadé(s).', $documentsCreated);
            }
            $this->addFlash('success', $successMessage);
            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/cancel', name: 'event_cancel', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function cancelEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé. Vous ne pouvez annuler que vos propres événements.');
        }

        // Vérifier si l'événement n'est pas déjà annulé
        if ($event->getStatus() === 'annulé') {
            $this->addFlash('warning', 'Cet événement est déjà annulé.');
            return $this->redirectToRoute('event_list');
        }

        try {
            // Sauvegarder l'ancien statut pour les logs
            $oldStatus = $event->getStatus();
            
            $event->setStatus('annulé');
            $em->flush();
            
            // Log de l'annulation pour le débogage
            error_log(sprintf(
                'Événement annulé - ID: %d, Titre: %s, Ancien statut: %s, Nouveau statut: %s, Utilisateur: %s',
                $event->getId(),
                $event->getTitle(),
                $oldStatus ?? 'NULL',
                $event->getStatus(),
                $this->getUser()->getUserIdentifier()
            ));

            // Notification aux participants
            $this->eventNotificationService->sendEventCancelNotification($event);
            
            // Notification administrateur pour l'annulation
            try {
                $this->adminNotificationService->notifyEventCancelled($event);
            } catch (\Exception $e) {
                error_log('Erreur notification admin annulation événement: ' . $e->getMessage());
            }

            // Notification globale pour l'annulation
            try {
                $this->globalNotificationService->notifyPlatformModification('annulé', 'event', $event);
            } catch (\Exception $e) {
                error_log('Erreur notification globale annulation événement: ' . $e->getMessage());
            }
            
            $this->addFlash('success', 'Événement annulé avec succès.');
            
        } catch (\Exception $e) {
            error_log('Erreur lors de l\'annulation de l\'événement: ' . $e->getMessage());
            $this->addFlash('error', 'Erreur lors de l\'annulation de l\'événement. Veuillez réessayer.');
        }
        
        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/{id}', name: 'event_show', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function showEvent(
        int $id,
        EventRepository $eventRepository,
        ParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $user = $this->getUser();
        if (!$user) {
            throw $this->createAccessDeniedException("Vous devez être connecté pour accéder à cet événement.");
        }

        /** @var User $user */
        $isCreator = ($event->getCreatedBy() && $event->getCreatedBy()->getId() === $user->getId()) ||
                     ($event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId());
        
        // Vérifier si l'utilisateur a le rôle ADMIN (accès total)
        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');

        // Vérifier si l'utilisateur est un participant ayant accepté l'invitation
        $hasAcceptedInvitation = false;
        if (!$isCreator && !$hasAdminAccess) {
            $invitationRepo = $entityManager->getRepository('App\Entity\Invitation');
            $acceptedInvitation = $invitationRepo->findOneBy([
                'email' => $user->getEmail(),
                'event' => $event,
                'status' => InvitationStatus::ACCEPTED->value
            ]);
            $hasAcceptedInvitation = $acceptedInvitation !== null;
        }

        if (!$isCreator && !$hasAdminAccess && !$hasAcceptedInvitation) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à cet événement. Vous ne pouvez voir que vos propres événements ou les événements que vous avez acceptés.");
        }

        // Récupérer le procès-verbal associé à cet événement (si c'est une réunion)
        $procesVerbal = null;
        if (strtolower($event->getCategory()) === 'réunion' || strtolower($event->getCategory()) === 'reunion') {
            $procesVerbal = $entityManager->getRepository('App\Entity\ProcesVerbal')->findByEvent($event);
        }

        // Récupérer les documents associés à l'événement
        $documents = $event->getDocuments();

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isCreator' => $isCreator,
            'hasAcceptedInvitation' => $hasAcceptedInvitation,
            'hasAdminAccess' => $hasAdminAccess,
            'procesVerbal' => $procesVerbal,
            'documents' => $documents,
        ]);
    }

    #[Route('/event/{id}/attendance', name: 'event_attendance', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function attendance(int $id, EventRepository $eventRepository, ParticipationRepository $participationRepository): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé. Vous ne pouvez voir l\'assistance que pour vos propres événements.');
        }

        $participations = $participationRepository->findBy(['event' => $event]);
        $going = [];
        $notGoing = [];
        $pending = [];

        foreach ($participations as $participation) {
            $user = $participation->getUser();
            $status = $participation->getInvitationStatus();
            match ($status) {
                'accepté' => $going[] = $user,
                'refusé' => $notGoing[] = $user,
                default => $pending[] = $user,
            };
        }

        return $this->render('event/attendance.html.twig', [
            'event' => $event,
            'going' => $going,
            'notGoing' => $notGoing,
            'pending' => $pending,
        ]);
    }

    #[Route('/event/{id}/training-attendance', name: 'event_training_attendance', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function trainingAttendance(int $id, EventRepository $eventRepository, ParticipationRepository $participationRepository): Response
    {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur ou créateur de l'événement
        $isOrganizer = $event->getOrganizer() === $user;
        $isCreator = $event->getCreatedBy() === $user;
        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');
        
        if (!$isOrganizer && !$isCreator && !$hasAdminAccess) {
            throw $this->createAccessDeniedException('Seul l\'organisateur peut gérer la liste de présence.');
        }

        // Vérifier que c'est bien un événement de type formation
        if (strtolower($event->getCategory()) !== 'formation') {
            $this->addFlash('error', 'La liste de présence n\'est disponible que pour les événements de type Formation.');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }

        // Vérifier que c'est le jour de l'événement (temporairement désactivé pour tests)
        /*
        $today = new \DateTime('today');
        $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
        
        if ($eventDate > $today) {
            $this->addFlash('warning', 'La gestion de présence ne sera disponible que le jour de l\'événement (' . $event->getDateHeure()->format('d/m/Y') . ').');
            return $this->redirectToRoute('event_show', ['id' => $event->getId()]);
        }
        */

        // Récupérer tous les participants qui ont accepté l'invitation
        $participations = $participationRepository->findBy([
            'event' => $event,
            'invitationStatus' => InvitationStatus::ACCEPTED->value
        ]);

        return $this->render('event/training_attendance.html.twig', [
            'event' => $event,
            'participations' => $participations,
            'isOrganizer' => $isOrganizer,
            'isCreator' => $isCreator,
            'hasAdminAccess' => $hasAdminAccess,
        ]);
    }

    #[Route('/event/{id}/update-training-attendance', name: 'event_update_training_attendance', methods: ['POST'], requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function updateTrainingAttendance(
        int $id, 
        Request $request,
        EventRepository $eventRepository, 
        ParticipationRepository $participationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $eventRepository->find($id);
        
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        $user = $this->getUser();
        
        // Vérifier que l'utilisateur est l'organisateur ou créateur de l'événement
        $isOrganizer = $event->getOrganizer() === $user;
        $isCreator = $event->getCreatedBy() === $user;
        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');
        
        if (!$isOrganizer && !$isCreator && !$hasAdminAccess) {
            return $this->json(['success' => false, 'message' => 'Non autorisé'], 403);
        }

        // Vérifier que c'est bien un événement de type formation
        if (strtolower($event->getCategory()) !== 'formation') {
            return $this->json(['success' => false, 'message' => 'Non disponible pour ce type d\'événement'], 400);
        }

        // Vérifier que c'est le jour de l'événement (temporairement désactivé pour tests)
        /*
        $today = new \DateTime('today');
        $eventDate = new \DateTime($event->getDateHeure()->format('Y-m-d'));
        
        if ($eventDate > $today) {
            return $this->json(['success' => false, 'message' => 'La gestion de présence ne sera disponible que le jour de l\'événement (' . $event->getDateHeure()->format('d/m/Y') . ')'], 403);
        }
        */

        // Récupérer les données de présence du formulaire
        $attendanceData = $request->request->all('attendance');
        $updatedCount = 0;
        
        foreach ($attendanceData as $participationId => $isPresent) {
            $participation = $participationRepository->find($participationId);
            
            if ($participation && $participation->getEvent() === $event) {
                $participation->setIsPresent($isPresent === '1');
                $updatedCount++;
            }
        }

        $entityManager->flush();

        return $this->json([
            'success' => true, 
            'message' => "$updatedCount participants mis à jour",
            'updatedCount' => $updatedCount
        ]);
    }

    #[Route('/event/{id}/delete', name: 'event_delete', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_USER')]
    public function deleteEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getCreatedBy() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé. Vous ne pouvez supprimer que vos propres événements.');
        }

        // Vérifier que l'événement est bien annulé avant de permettre la suppression
        if ($event->getStatus() !== 'annulé') {
            $this->addFlash('error', 'Seuls les événements annulés peuvent être supprimés définitivement.');
            return $this->redirectToRoute('event_list');
        }

        try {
            $eventTitle = $event->getTitle();
            
            // Notification administrateur pour la suppression
            try {
                $this->adminNotificationService->notifyEventDeleted($event);
            } catch (\Exception $e) {
                error_log('Erreur notification admin suppression événement: ' . $e->getMessage());
            }

            // Notification globale pour la suppression
            try {
                $this->globalNotificationService->notifyPlatformModification('supprimé', 'event', $event);
            } catch (\Exception $e) {
                error_log('Erreur notification globale suppression événement: ' . $e->getMessage());
            }

            // Supprimer l'événement (les relations seront supprimées en cascade)
            $em->remove($event);
            $em->flush();

            $this->addFlash('success', sprintf('L\'événement "%s" a été supprimé définitivement.', $eventTitle));
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de la suppression : ' . $e->getMessage());
        }

        return $this->redirectToRoute('event_list');
    }

    #[Route('/event/cancelled', name: 'event_cancelled_list')]
    #[IsGranted('ROLE_USER')]
    public function cancelledEventsList(EntityManagerInterface $em): Response
    {
        // Récupérer uniquement les événements annulés créés par l'utilisateur
        $cancelledEvents = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('e')
            ->where('e.createdBy = :creator')
            ->andWhere('e.status = :cancelled')
            ->setParameter('creator', $this->getUser())
            ->setParameter('cancelled', 'annulé')
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('event/cancelled_list.html.twig', [
            'events' => $cancelledEvents,
        ]);
    }

    #[Route('/api/salles-disponibles', name: 'api_salles_disponibles', methods: ['GET'])]
    public function sallesDisponibles(Request $request, SalleDisponibiliteService $disponibiliteService, EntityManagerInterface $em): JsonResponse
    {
        $dateHeure = $request->query->get('dateHeure');
        $duree = $request->query->get('duree', 60);

        if (!$dateHeure) {
            return new JsonResponse(['error' => 'Date manquante'], 400);
        }

        try {
            $dateHeure = new \DateTime($dateHeure);
            $fin = (clone $dateHeure)->modify("+{$duree} minutes");

            // Récupérer toutes les salles actives
            $salles = $em->getRepository(Salle::class)->findActiveSalles();
            $sallesDisponibles = [];
            $sallesIndisponibles = [];

            foreach ($salles as $salle) {
                // Vérifier que la salle n'est pas désactivée
                if (!$salle->isDisponible()) {
                    $sallesIndisponibles[] = [
                        'id' => $salle->getId(),
                        'nom' => $salle->getNom(),
                        'raison' => 'Salle désactivée'
                    ];
                    continue;
                }
                
                // Vérifier que la salle n'est pas actuellement occupée
                $maintenant = new \DateTime();
                $reservationActuelle = $em->getRepository(\App\Entity\Reservation::class)->findReservationActuelle($salle, $maintenant);
                if ($reservationActuelle) {
                    $sallesIndisponibles[] = [
                        'id' => $salle->getId(),
                        'nom' => $salle->getNom(),
                        'raison' => 'Actuellement occupée'
                    ];
                    continue;
                }
                
                // Vérifier qu'il n'y a pas de réservation prochaine
                $prochaineReservation = $em->getRepository(\App\Entity\Reservation::class)->findProchaineReservation($salle, $maintenant);
                if ($prochaineReservation) {
                    $sallesIndisponibles[] = [
                        'id' => $salle->getId(),
                        'nom' => $salle->getNom(),
                        'raison' => 'Réservée bientôt'
                    ];
                    continue;
                }
                
                // Vérifier la disponibilité pour le créneau spécifique
                $estDisponible = $disponibiliteService->estDisponible($salle, $dateHeure, $fin);
                
                if ($estDisponible) {
                    // Vérification du délai tampon (1 seconde) avant la prochaine réservation
                    $prochaineReservation = $em->getRepository(\App\Entity\Reservation::class)->findProchaineReservation($salle, $fin);
                    $delaiTampon = true;
                    
                    if ($prochaineReservation) {
                        $diff = $fin->diff($prochaineReservation->getDateDebut());
                        $diffInSeconds = ($diff->days * 24 * 60 * 60) + ($diff->h * 3600) + ($diff->i * 60) + $diff->s;
                        if ($diffInSeconds <= 1 && !$diff->invert) {
                            $delaiTampon = false;
                        }
                    }
                    
                    if ($delaiTampon) {
                        $sallesDisponibles[] = [
                            'id' => $salle->getId(),
                            'nom' => $salle->getNom(),
                            'capacite' => $salle->getCapacite(),
                            'type' => $salle->getType() ?? 'réunion'
                        ];
                    } else {
                        $sallesIndisponibles[] = [
                            'id' => $salle->getId(),
                            'nom' => $salle->getNom(),
                            'raison' => 'Réservation trop proche'
                        ];
                    }
                } else {
                    // Déterminer la raison de l'indisponibilité
                    $raison = 'Indisponible';
                    
                    // Vérifier les heures d'ouverture manuellement
                    $heureOuverture = $salle->getDebutReservation();
                    $heureFermeture = $salle->getFinReservation();
                    
                    if ($heureOuverture && $heureFermeture) {
                        $debutHeure = $dateHeure->format('H:i');
                        $finHeure = $fin->format('H:i');
                        $ouvertureHeure = $heureOuverture->format('H:i');
                        $fermetureHeure = $heureFermeture->format('H:i');
                        
                        if ($fermetureHeure < $ouvertureHeure) {
                            // Cas de passage minuit
                            if (!(($debutHeure >= $ouvertureHeure || $debutHeure <= $fermetureHeure) &&
                                  ($finHeure >= $ouvertureHeure || $finHeure <= $fermetureHeure) &&
                                  ($debutHeure <= $finHeure || $debutHeure >= $ouvertureHeure))) {
                                $raison = 'Hors heures d\'ouverture';
                            }
                        } else {
                            // Cas normal
                            if ($debutHeure < $ouvertureHeure || $finHeure > $fermetureHeure) {
                                $raison = 'Hors heures d\'ouverture';
                            }
                        }
                    }
                    
                    // Si ce n'est pas un problème d'heures, c'est probablement une réservation
                    if ($raison === 'Indisponible') {
                        $raison = 'Déjà réservée';
                    }
                    
                    $sallesIndisponibles[] = [
                        'id' => $salle->getId(),
                        'nom' => $salle->getNom(),
                        'raison' => $raison
                    ];
                }
            }

            return new JsonResponse([
                'disponibles' => $sallesDisponibles,
                'indisponibles' => $sallesIndisponibles,
                'total_disponibles' => count($sallesDisponibles),
                'total_indisponibles' => count($sallesIndisponibles)
            ]);
            
        } catch (\Exception $e) {
            return new JsonResponse(['error' => 'Erreur lors du traitement de la date: ' . $e->getMessage()], 400);
        }
    }
}
