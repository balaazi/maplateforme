<?php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\CalendarEvent;
use App\Form\EventFormType;
use App\Repository\EventRepository;
use App\Repository\ParticipationRepository;
use App\Service\EventNotificationService;
use App\Service\GoogleCalendarService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_ORGANISATEUR')]
class EventController extends AbstractController
{
    private EventNotificationService $notificationService;

    public function __construct(EventNotificationService $notificationService)
    {
        $this->notificationService = $notificationService;
    }

    #[Route('/event/create', name: 'event_create')]
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

            try {
                if (!$calendarService->isAuthenticated()) {
                    $session->set('intended_route', 'event_create');
                    return $this->redirectToRoute('google_calendar_connect');
                }

                $calendarService->exportToGoogleCalendar($calendarEvent);
                $calendarService->synchronizeCalendars();

                $this->addFlash('success', 'Événement créé et synchronisé dans les deux sens avec Google Calendar.');
            } catch (\Exception $e) {
                if (str_contains($e->getMessage(), 'Invalid token format') || str_contains($e->getMessage(), 'Token has expired')) {
                    $session->set('intended_route', 'event_create');
                    return $this->redirectToRoute('google_calendar_connect');
                }

                $this->addFlash('warning', 'Événement créé mais problème de synchronisation : ' . $e->getMessage());
            }

            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/create.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/event/list', name: 'event_list')]
    public function list(EntityManagerInterface $em): Response
    {
        $events = $em->getRepository(Event::class)
            ->createQueryBuilder('e')
            ->select('e')
            ->where('e.organizer = :organizer')
            ->setParameter('organizer', $this->getUser())
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();

        return $this->render('event/list.html.twig', [
            'events' => $events,
        ]);
    }

    #[Route('/event/{id}/edit', name: 'event_edit', requirements: ['id' => '\d+'])]
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
            $this->notificationService->sendEventUpdateNotification($event);
            $this->addFlash('success', 'Événement modifié avec succès.');
            return $this->redirectToRoute('event_list');
        }

        return $this->render('event/edit.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
        ]);
    }

    #[Route('/event/{id}/cancel', name: 'event_cancel', requirements: ['id' => '\d+'])]
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

        $this->notificationService->sendEventCancelNotification($event);
        $this->addFlash('success', 'Événement annulé avec succès.');
        return $this->redirectToRoute('event_list');
    }

#[Route('/event/{id}', name: 'event_show', requirements: ['id' => '\d+'])]
public function showEvent(
    int $id,
    EventRepository $eventRepository,
    ParticipationRepository $participationRepository
): Response {
    $event = $eventRepository->find($id);
    if (!$event) {
        throw $this->createNotFoundException('Événement non trouvé.');
    }

    $user = $this->getUser();
    if (!$user) {
        throw $this->createAccessDeniedException("Vous devez être connecté pour accéder à cet événement.");
    }

    $isOrganizer = $event->getOrganizer() && $event->getOrganizer()->getId() === $user->getId();
    $isParticipant = $participationRepository->findOneBy([
        'event' => $event,
        'user' => $user,
    ]) !== null;

    if (!$isOrganizer && !$isParticipant) {
        throw $this->createAccessDeniedException("Vous n'êtes pas inscrit à cet événement.");
    }

    return $this->render('event/show.html.twig', [
        'event' => $event,
        'isOrganizer' => $isOrganizer,
    ]);
}


    #[Route('/event/{id}/attendance', name: 'event_attendance', requirements: ['id' => '\d+'])]
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
}
