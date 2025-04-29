<?php
// src/Controller/InvitationController.php

namespace App\Controller;

use App\Entity\Invitation;
use App\Form\InvitationType;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;

class InvitationController extends AbstractController
{
    #[Route('/organizer/invitations', name: 'invitation_index')]
    public function index(InvitationRepository $invitationRepository): Response
    {
        return $this->render('invitation/index.html.twig', [
            'invitations' => $invitationRepository->findAll(),
        ]);
    }

    #[Route('/organizer/invitations/new', name: 'invitation_new')]
    public function new(Request $request, EntityManagerInterface $entityManager, MailerInterface $mailer): Response
    {
        $invitation = new Invitation();
        $form = $this->createForm(InvitationType::class, $invitation);
        
        $form->handleRequest($request);
        
        if ($form->isSubmitted() && $form->isValid()) {
            // Générer un token unique
            $invitation->setToken(bin2hex(random_bytes(32)));
            $invitation->setStatus('pending');
            $invitation->setCreatedAt(new \DateTimeImmutable());
            
            $entityManager->persist($invitation);
            $entityManager->flush();
            
            // Envoyer l'email
            $this->sendInvitationEmail($invitation, $mailer);
            
            return $this->redirectToRoute('invitation_index');
        }
        
        return $this->render('invitation/new.html.twig', [
            'form' => $form->createView(),
        ]);
    }

    #[Route('/invitation/respond/{token}/{response}', name: 'invitation_respond')]
    public function respond(string $token, string $response, EntityManagerInterface $entityManager): Response
    {
        $invitation = $entityManager->getRepository(Invitation::class)->findOneBy(['token' => $token]);
        
        if (!$invitation) {
            throw $this->createNotFoundException('Invitation non trouvée');
        }
        
        $invitation->setStatus($response);
        $invitation->setUpdatedAt(new \DateTimeImmutable());
        $entityManager->flush();
        
        return $this->render('invitation/response.html.twig', [
            'response' => $response,
            'invitation' => $invitation,
        ]);
    }
    
    private function sendInvitationEmail(Invitation $invitation, MailerInterface $mailer): void
    {
        $email = (new Email())
            ->from('organizer@example.com')
            ->to($invitation->getEmail())
            ->subject('Invitation à un événement')
            ->html($this->renderView('emails/invitation.html.twig', [
                'invitation' => $invitation,
            ]));
            
        $mailer->send($email);
    }
}