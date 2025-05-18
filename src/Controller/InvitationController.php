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

#[Route('/organizer/invitations')]
class InvitationController extends AbstractController
{
    #[Route('/', name: 'invitation_index')]
    public function index(InvitationRepository $invitationRepository): Response
    {
        return $this->render('invitation/index.html.twig', [
            'invitations' => $invitationRepository->findAll(),
        ]);
    }

    #[Route('/organizer/invitations/new/{eventId}', name: 'invitation_new')]
    public function new(
        Request $request,
        int $eventId,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        EventRepository $eventRepository
    ): Response{
        $event = $eventRepository->find($eventId);

        if (!$event) {
            $this->addFlash('error', 'Événement non trouvé');
            return $this->redirectToRoute('event_index');
        }

        $invitation = new Invitation();
        $form = $this->createForm(InvitationType::class, $invitation);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $invitation->setToken(bin2hex(random_bytes(32)))
                ->setStatus('pending')
                ->setCreatedAt(new \DateTimeImmutable())
                ->setEvent($event);

            $entityManager->persist($invitation);
            $entityManager->flush();

            $this->sendInvitationEmail($invitation, $mailer);
            $this->addFlash('success', 'Invitation envoyée avec succès');

            return $this->redirectToRoute('invitation_list', ['eventId' => $eventId]);
        }

        return $this->render('invitation/list.html.twig', [
            'event' => $event, // Doit être passé explicitement
            'invitations' => $invitation
        ]);
    }

    #[Route('/list/{eventId}', name: 'invitation_list')]
    public function list(
        int $eventId,
        EventRepository $eventRepository,
        InvitationRepository $invitationRepository
    ): Response {
        $event = $eventRepository->find($eventId);

        if (!$event) {
            $this->addFlash('error', 'Événement non trouvé');
            return $this->redirectToRoute('event_index');
        }

        return $this->render('invitation/list.html.twig', [
            'event' => $event,
            'invitations' => $invitationRepository->findBy(['event' => $event]),
        ]);
    }

    #[Route('/respond/{token}/{response}', name: 'invitation_respond')]
    public function respond(
        string $token,
        string $response,
        EntityManagerInterface $entityManager
    ): Response {
        $invitation = $entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        $invitation->setStatus($response)
            ->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();

        return $this->render('invitation/response.html.twig', [
            'response' => $response,
            'invitation' => $invitation,
        ]);
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