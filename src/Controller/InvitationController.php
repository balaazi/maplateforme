<?php
// src/Controller/InvitationController.php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Event;
use App\Form\InvitationType;
use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Participation;
use App\Entity\User;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/organizer')]
#[IsGranted('ROLE_ORGANISATEUR')]
class InvitationController extends AbstractController
{
    #[Route('/invitations/{eventId}/new', name: 'invitation_new', requirements: ['eventId' => '\d+'], methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        int $eventId,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        EventRepository $eventRepository
    ): Response {
        $event = $eventRepository->find($eventId);

        if (!$event) {
            $this->addFlash('error', 'Événement non trouvé');
            return $this->redirectToRoute('event_list');
        }

        // Vérifier si l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à gérer les invitations de cet événement.');
        }

        $invitation = new Invitation();
        $form = $this->createForm(InvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation->setToken(bin2hex(random_bytes(32)))
                ->setStatus('pending')
                ->setCreatedAt(new \DateTime())
                ->setEvent($event);

            $entityManager->persist($invitation);
            $entityManager->flush();

            $this->sendInvitationEmail($invitation, $mailer);
            $this->addFlash('success', 'Invitation envoyée avec succès');

            return $this->redirectToRoute('invitation_index', ['eventId' => $eventId]);
        }

        return $this->render('invitation/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event
        ]);
    }

    #[Route('/invitations/{eventId}', name: 'invitation_index', requirements: ['eventId' => '\d+'], methods: ['GET'])]
    public function index(
        int $eventId,
        Request $request,
        EventRepository $eventRepository,
        InvitationRepository $invitationRepository
    ): Response {
        // Debug information
        $user = $this->getUser();
        $roles = $user ? $user->getRoles() : ['not authenticated'];
        
        // Log the request information
        $this->addFlash('info', sprintf(
            'Request Debug: Method: %s, Path: %s, EventId: %s',
            $request->getMethod(),
            $request->getPathInfo(),
            $eventId
        ));
        
        $event = $eventRepository->find($eventId);
        
        if (!$event) {
            $this->addFlash('error', sprintf(
                'Événement non trouvé (ID: %d). Debug: User: %s, Roles: %s',
                $eventId,
                $user ? $user->getUserIdentifier() : 'none',
                implode(', ', $roles)
            ));
            return $this->redirectToRoute('event_list');
        }

        // Debug information
        if ($event->getOrganizer() !== $user) {
            $this->addFlash('error', sprintf(
                'Accès refusé. Organisateur attendu: %s, Utilisateur actuel: %s, Roles: %s',
                $event->getOrganizer() ? $event->getOrganizer()->getUserIdentifier() : 'none',
                $user ? $user->getUserIdentifier() : 'none',
                implode(', ', $roles)
            ));
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les invitations de cet événement.');
        }

        $invitations = $invitationRepository->findBy(['event' => $event]);

        return $this->render('invitation/index.html.twig', [
            'event' => $event,
            'invitations' => $invitations,
            'debug' => [
                'user' => $user ? $user->getUserIdentifier() : 'none',
                'roles' => $roles,
                'eventId' => $eventId,
                'requestPath' => $request->getPathInfo(),
                'requestMethod' => $request->getMethod()
            ]
        ]);
    }

    #[Route('/invitations/{id}/cancel', name: 'invitation_cancel', methods: ['GET'])]
    public function cancel(
        Invitation $invitation,
        EntityManagerInterface $entityManager
    ): Response {
        $event = $invitation->getEvent();
        
        // Check if the current user is the event organizer
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à annuler cette invitation.');
        }

        // Only pending invitations can be cancelled
        if ($invitation->getStatus() !== 'pending') {
            $this->addFlash('error', 'Seules les invitations en attente peuvent être annulées.');
            return $this->redirectToRoute('invitation_index', ['eventId' => $event->getId()]);
        }

        $entityManager->remove($invitation);
        $entityManager->flush();

        $this->addFlash('success', 'L\'invitation a été annulée avec succès.');
        return $this->redirectToRoute('invitation_index', ['eventId' => $event->getId()]);
    }

    private function sendInvitationEmail(Invitation $invitation, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from($this->getParameter('app.mailer_from'))
            ->to($invitation->getEmail())
            ->subject('Invitation à l\'événement: ' . $invitation->getEvent()->getTitle())
            ->html($this->renderView('emails/invitation.html.twig', [
                'invitation' => $invitation,
            ]));

        $mailer->send($email);
    }
}