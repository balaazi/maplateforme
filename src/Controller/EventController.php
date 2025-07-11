<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\CalendarEvent;
use App\Entity\Invitation;
use App\Entity\Participation;
use App\Entity\User;
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

class EventController extends AbstractController
{
    private EventNotificationService $eventNotificationService;
    private AdminNotificationService $adminNotificationService;
    private NotificationService $notificationService;
    private GlobalNotificationService $globalNotificationService;
    private ReminderService $reminderService;

    public function __construct(
        EventNotificationService $eventNotificationService,
        AdminNotificationService $adminNotificationService,
        NotificationService $notificationService,
        GlobalNotificationService $globalNotificationService,
        ReminderService $reminderService
    ) {
        $this->eventNotificationService = $eventNotificationService;
        $this->adminNotificationService = $adminNotificationService;
        $this->notificationService = $notificationService;
        $this->globalNotificationService = $globalNotificationService;
        $this->reminderService = $reminderService;
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
            $event->setOrganizer($this->getUser());

            $calendarEvent = new CalendarEvent();
            $calendarEvent->setTitle($event->getTitle());
            $calendarEvent->setDescription($event->getDescription());
            $calendarEvent->setStart($event->getDateHeure());

            $end = (clone $event->getDateHeure())->modify('+' . $event->getDuree() . ' minutes');
            $calendarEvent->setEnd($end);

            $entityManager->persist($event);
            $entityManager->persist($calendarEvent);
            $entityManager->flush();

            // ✅ NOUVEAUTÉ : Créer automatiquement des rappels pour l'événement
            try {
                $reminders = $this->reminderService->createReminderSchedule($event, [1440, 60, 15]); // 24h, 1h, 15min avant
                $this->addFlash('success', sprintf(
                    'Événement créé avec succès ! %d rappel(s) automatique(s) programmé(s).', 
                    count($reminders)
                ));
            } catch (\Exception $e) {
                error_log('Erreur création rappels automatiques: ' . $e->getMessage());
                $this->addFlash('warning', 'Événement créé mais problème avec les rappels automatiques. Veuillez vérifier vos préférences de notification.');
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
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function list(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('e')
            ->where('e.organizer = :organizer')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->setParameter('organizer', $this->getUser())
            ->setParameter('cancelled', 'annulé')
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function edit(int $id, Request $request, EntityManagerInterface $em): Response
    {
        $event = $em->getRepository(Event::class)->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé.');
        }

        $form = $this->createForm(EventFormType::class, $event);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $em->flush();
            
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
            
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/cancel', name: 'event_cancel', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function cancelEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé.');
        }

        $event->setStatus('annulé');
        $em->flush();

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
        $isOrganizer = $event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId();
        
        // Vérifier s'il existe déjà une participation
        $participation = $participationRepository->findOneBy([
            'event' => $event,
            'user' => $user,
        ]);

        $isParticipant = $participation !== null;

        // Si l'utilisateur n'est ni organisateur ni participant, vérifier les invitations
        if (!$isOrganizer && !$isParticipant) {
            $invitationRepository = $entityManager->getRepository(\App\Entity\Invitation::class);
            
            // Vérifier avec différents statuts d'invitation
            $invitation = $invitationRepository->findOneBy([
                'event' => $event,
                'email' => $user->getUserIdentifier(),
                'status' => 'accepted'
            ]);

            // Si pas trouvé avec 'accepted', essayer avec 'accepté'
            if (!$invitation) {
                $invitation = $invitationRepository->findOneBy([
                    'event' => $event,
                    'email' => $user->getUserIdentifier(),
                    'status' => 'accepté'
                ]);
            }

            if ($invitation) {
                // Créer automatiquement une participation pour l'invitation acceptée
                $participation = new \App\Entity\Participation();
                $participation->setUser($user);
                $participation->setEvent($event);
                $participation->setInvitationStatus('accepté');
                $participation->setIsPresent(false);
                $participation->setCreatedAt(new \DateTime());
                
                $entityManager->persist($participation);
                $entityManager->flush();
                
                $isParticipant = true;
            }
        }

        // Si l'utilisateur est organisateur, créer une participation automatique s'il n'en a pas
        if ($isOrganizer && !$participation) {
            $participation = new \App\Entity\Participation();
            $participation->setUser($user);
            $participation->setEvent($event);
            $participation->setInvitationStatus('accepté');
            $participation->setIsPresent(true); // L'organisateur est présent par défaut
            $participation->setCreatedAt(new \DateTime());
            
            $entityManager->persist($participation);
            $entityManager->flush();
            
            $isParticipant = true;
        }

        // Vérifier si l'utilisateur a le rôle ADMIN (accès total)
        $hasAdminAccess = $this->isGranted('ROLE_ADMIN');

        if (!$isOrganizer && !$isParticipant && !$hasAdminAccess) {
            throw $this->createAccessDeniedException("Vous n'êtes pas autorisé à accéder à cet événement.");
        }

        return $this->render('event/show.html.twig', [
            'event' => $event,
            'isOrganizer' => $isOrganizer,
        ]);
    }

    #[Route('/event/{id}/attendance', name: 'event_attendance', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function attendance(int $id, EventRepository $eventRepository, ParticipationRepository $participationRepository): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé.');
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

    #[Route('/event/{id}/delete', name: 'event_delete', requirements: ['id' => '\d+'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function deleteEvent(int $id, EventRepository $eventRepository, EntityManagerInterface $em): Response
    {
        $event = $eventRepository->find($id);
        if (!$event) {
            throw $this->createNotFoundException('Événement non trouvé.');
        }

        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Non autorisé.');
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
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function cancelledEventsList(EntityManagerInterface $em): Response
    {
        // Récupérer uniquement les événements annulés
        $cancelledEvents = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('e')
            ->where('e.organizer = :organizer')
            ->andWhere('e.status = :cancelled')
            ->setParameter('organizer', $this->getUser())
            ->setParameter('cancelled', 'annulé')
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('event/cancelled_list.html.twig', [
            'events' => $cancelledEvents,
        ]);
    }
}
