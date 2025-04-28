<?php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Event;
use App\Entity\User;
use App\Enum\InvitationStatus;
use App\Service\InvitationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Csrf\CsrfToken;
use Symfony\Component\Security\Csrf\CsrfTokenManagerInterface;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/invitation', name: 'invitation_')]
class InvitationController extends AbstractController
{
    public function __construct(
        private EntityManagerInterface $em,
        private CsrfTokenManagerInterface $csrfTokenManager
    ) {}

    // Redirection vers le dashboard
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->redirectToRoute('invitation_dashboard');
    }

    // Tableau de bord des invitations du participant connecté
    #[Route('/dashboard', name: 'dashboard', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function dashboard(): Response
    {
    $user = $this->getUser();
    $invitations = $this->em->getRepository(Invitation::class)->findBy([
        'participant' => $user
    ]);

    // Ajoutez ce débogage pour vérifier les invitations

    return $this->render('invitation/dashboard.html.twig', [
        'invitations' => $invitations
]);
    }

    // Répondre à une invitation
    #[Route('/respond/{id}', name: 'respond', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function respond(Request $request, Invitation $invitation): Response
    {
        // Vérifie que l'utilisateur connecté est bien le participant
        if ($invitation->getParticipant() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Vous n'avez pas accès à cette invitation.");
        }

        // Vérification CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('invitation_respond', $submittedToken))) {
            $this->addFlash('error', 'Token de sécurité invalide.');
            return $this->redirectToRoute('invitation_dashboard');
        }

        // Traitement de la réponse
        $response = $request->request->get('response');
        $status = match ($response) {
            'accepted' => InvitationStatus::ACCEPTED,
            'declined' => InvitationStatus::DECLINED,
            default => InvitationStatus::PENDING,
        };

        $invitation->setStatus($status);
        $invitation->setRespondedAt(new \DateTimeImmutable()); // ✅ Date de réponse
        $this->em->flush();

        $this->addFlash('success', 'Votre réponse a été enregistrée.');
        return $this->redirectToRoute('invitation_dashboard');
    }

    // Envoyer une invitation à un utilisateur pour un événement
    #[Route('/invite/{eventId}/{userId}', name: 'invite', methods: ['GET'])]
    #[IsGranted('ROLE_ORGANIZER')] // à adapter à ton système de rôles
    public function invite(int $eventId, int $userId, InvitationService $invitationService): Response
    {
        $event = $this->em->getRepository(Event::class)->find($eventId);
        $user = $this->em->getRepository(User::class)->find($userId);

        if (!$event || !$user) {
            throw $this->createNotFoundException('Événement ou utilisateur introuvable.');
        }

        // Vérifie si une invitation existe déjà
        $existing = $this->em->getRepository(Invitation::class)->findOneBy([
            'event' => $event,
            'participant' => $user
        ]);

        if ($existing) {
            $this->addFlash('warning', 'Une invitation existe déjà pour cet utilisateur.');
            return $this->redirectToRoute('invitation_dashboard');
        }

        try {
            $invitationService->sendInvitation($event, $user);
            $this->addFlash('success', 'Invitation envoyée avec succès !');
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur lors de l\'envoi de l\'invitation.');
        }

        return $this->redirectToRoute('invitation_dashboard');
    }
    #[Route('/event/{id}/invitations', name: 'organizer_invitations')]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function viewInvitations(Event $event): Response
    {
        // Vérifie que c’est bien lui l’organisateur
        if ($event->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException("Accès refusé.");
        }

        return $this->render('invitation/organizer_dashboard.html.twig', [
            'event' => $event,
            'invitations' => $event->getInvitations(),
        ]);
    }
    #[Route('/cancel/{id}', name: 'cancel', methods: ['POST'])]
    #[IsGranted('ROLE_ORGANISATEUR')]
    public function cancel(
        Request $request, 
        Invitation $invitation, 
        InvitationService $invitationService
    ): Response {
        // Vérification CSRF
        $submittedToken = $request->request->get('_token');
        if (!$this->csrfTokenManager->isTokenValid(new CsrfToken('delete'.$invitation->getId(), $submittedToken))) {
            $this->addFlash('error', 'Token CSRF invalide.');
            return $this->redirectToRoute('invitation_organizer_invitations', ['id' => $invitation->getEvent()->getId()]);
        }
    
        // Vérification des droits
        if ($invitation->getEvent()->getOrganizer() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }
    
        try {
            $invitationService->cancelInvitation($invitation);
            $this->addFlash('success', 'Invitation annulée avec succès.');
        } catch (\Exception $e) {
            $this->addFlash('error', "Erreur lors de l'annulation : ".$e->getMessage());
        }
    
        return $this->redirectToRoute('invitation_organizer_invitations', ['id' => $invitation->getEvent()->getId()]);
    }

}
