<?php
// src/Controller/InvitationController.php

namespace App\Controller;

use App\Entity\Invitation;
use App\Entity\Event;
use App\Form\InvitationType;
use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use App\Repository\UserRepository;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Annotation\Route;
use App\Entity\Participation;
use App\Entity\User;
use App\Enum\InvitationStatus;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Service\GlobalNotificationService;
use App\Service\InvitationExpirationService;

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
        EventRepository $eventRepository,
        UserRepository $userRepository,
        DepartementRepository $departementRepository,
        GlobalNotificationService $globalNotificationService
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

        // Récupérer les données pour les options de ciblage
        $departements = $departementRepository->findBy(['actif' => true], ['nom' => 'ASC']);
        $users = $userRepository->findBy([], ['nom' => 'ASC', 'prenom' => 'ASC']);

        // Traitement direct des invitations sans formulaire Symfony
        if ($request->isMethod('POST')) {
            // Récupérer les données du ciblage depuis la requête
            $targetingType = $request->request->get('targeting_type');
            $targetingData = $request->request->get('targeting_data');

            if ($targetingType) {
                            // Log pour débogage - Afficher les données du formulaire
            error_log("InvitationController: Traitement invitation - Type: $targetingType, Data: " . print_r($targetingData, true));
            error_log("InvitationController: Données brutes du formulaire: " . print_r($request->request->all(), true));
                
                // Ajouter des logs pour le debug des invitations par département
                if ($targetingType === 'department') {
                    error_log("Envoi d'invitation par département: ID=$targetingData");
                    
                    // Vérifier si le département existe
                    $departement = $entityManager->getRepository('App\\Entity\\Departement')->find($targetingData);
                    if (!$departement) {
                        error_log("ERREUR: Département ID=$targetingData non trouvé");
                        $this->addFlash('error', 'Département non trouvé');
                        return $this->redirectToRoute('invitation_new', ['eventId' => $eventId]);
                    }
                    
                    // Vérifier les utilisateurs associés à ce département
                    $usersInDept = $userRepository->findBy(['departement' => $departement]);
                    error_log("Utilisateurs trouvés dans le département " . $departement->getNom() . ": " . count($usersInDept));
                    
                    if (empty($usersInDept)) {
                        error_log("ERREUR: Aucun utilisateur trouvé dans le département " . $departement->getNom());
                        $this->addFlash('warning', 'Aucun utilisateur trouvé dans le département ' . $departement->getNom());
                    }
                    
                    foreach ($usersInDept as $user) {
                        error_log("  - " . $user->getEmail() . " (" . $user->getFullName() . ")");
                    }
                }
                
                $this->processTargetedInvitations(
                    $targetingType,
                    $targetingData,
                    $event,
                    new Invitation(), // Invitation vide comme base
                    $entityManager,
                    $mailer,
                    $userRepository,
                    $globalNotificationService
                );

                $this->addFlash('success', 'Invitation(s) envoyée(s) avec succès');
                return $this->redirectToRoute('invitation_index', ['eventId' => $eventId]);
            } else {
                $this->addFlash('error', 'Veuillez sélectionner un type de destinataire');
                error_log("InvitationController: Aucun type de ciblage reçu");
            }
        }

        // Créer un formulaire vide pour la compatibilité du template (non utilisé)
        $invitation = new Invitation();
        $form = $this->createForm(InvitationType::class, $invitation);

        return $this->render('invitation/new.html.twig', [
            'form' => $form->createView(),
            'event' => $event,
            'departements' => $departements,
            'users' => $users
        ]);
    }

    private function processTargetedInvitations(
        $targetingType,
        $targetingData,
        Event $event,
        Invitation $baseInvitation,
        EntityManagerInterface $entityManager,
        MailerInterface $mailer,
        UserRepository $userRepository,
        GlobalNotificationService $globalNotificationService
    ): void {
        $recipients = [];

        error_log("processTargetedInvitations: Type=$targetingType, Data=" . print_r($targetingData, true));

        switch ($targetingType) {
            case 'all':
                // Tous les employés
                $recipients = $userRepository->findAll();
                error_log("processTargetedInvitations: Mode 'all' - " . count($recipients) . " utilisateurs trouvés");
                break;

            case 'department':
                // Par département
                if ($targetingData) {
                    // Récupérer le département pour plus d'informations
                    $departement = $entityManager->getRepository('App\\Entity\\Departement')->find($targetingData);
                    $deptName = $departement ? $departement->getNom() : 'Inconnu';
                    
                    $recipients = $userRepository->findBy(['departement' => $targetingData]);
                    error_log("processTargetedInvitations: Mode 'department' - " . count($recipients) . " utilisateurs trouvés pour département ID=$targetingData ($deptName)");
                    
                    // Vérifier si les destinataires sont bien trouvés
                    if (empty($recipients)) {
                        error_log("ATTENTION: Aucun utilisateur trouvé pour le département ID=$targetingData ($deptName)");
                    } else {
                        foreach ($recipients as $user) {
                            error_log("  - Destinataire trouvé: " . $user->getEmail() . " (" . $user->getFullName() . ")");
                        }
                    }
                }
                break;

            case 'specific':
                // Employés spécifiques
                if ($targetingData) {
                    // Les données peuvent être sous forme de chaîne séparée par des virgules
                    $userIds = is_array($targetingData) ? $targetingData : explode(',', $targetingData);
                    $userIds = array_filter(array_map('trim', $userIds)); // Nettoyer les espaces
                    if (!empty($userIds)) {
                        $recipients = $userRepository->findBy(['id' => $userIds]);
                        error_log("processTargetedInvitations: Mode 'specific' - " . count($recipients) . " utilisateurs trouvés pour IDs: " . implode(',', $userIds));
                    }
                }
                break;
        }

        // Créer et envoyer les invitations pour chaque destinataire
        error_log("processTargetedInvitations: Début de la boucle d'envoi pour " . count($recipients) . " destinataires");
        
        foreach ($recipients as $user) {
            $invitation = new Invitation();
            $invitation->setName($user->getNom() . ' ' . $user->getPrenom())
                ->setEmail($user->getEmail())
                ->setToken(bin2hex(random_bytes(32)))
                ->setStatus('pending')
                ->setCreatedAt(new \DateTime())
                ->setEvent($event);

            $entityManager->persist($invitation);
            
            try {
                $globalNotificationService->notifyPlatformModification('créé', 'invitation', $invitation);
            } catch (\Exception $e) {
                error_log('Erreur notification globale création invitation: ' . $e->getMessage());
            }

            try {
                $this->sendInvitationEmail($invitation, $mailer);
                error_log("Invitation envoyée avec succès à: " . $user->getEmail());
            } catch (\Exception $e) {
                error_log("Erreur envoi email à " . $user->getEmail() . ": " . $e->getMessage());
            }
        }

        $entityManager->flush();
        error_log("processTargetedInvitations: " . count($recipients) . " invitations traitées et sauvegardées");
    }

    #[Route('/invitations/{eventId}', name: 'invitation_index', requirements: ['eventId' => '\d+'], methods: ['GET'])]
    public function index(
        int $eventId,
        Request $request,
        EventRepository $eventRepository,
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager,
        InvitationExpirationService $expirationService
    ): Response {
        $user = $this->getUser();
        $event = $eventRepository->find($eventId);
        
        if (!$event) {
            $this->addFlash('error', 'Événement non trouvé');
            return $this->redirectToRoute('event_list');
        }

        // Vérifier si l'utilisateur est l'organisateur de l'événement
        if ($event->getOrganizer() !== $user) {
            throw $this->createAccessDeniedException('Vous n\'êtes pas autorisé à voir les invitations de cet événement.');
        }

        $invitations = $invitationRepository->findBy(['event' => $event]);
        
        // Vérifier et expirer automatiquement les invitations expirées
        $expiredCount = 0;
        foreach ($invitations as $invitation) {
            // Forcer l'expiration avec un délai d'un jour
            if ($invitation->checkAndMarkAsExpired(1)) {
                $expiredCount++;
                error_log(sprintf(
                    "Invitation expirée - ID: %d, Email: %s, Type d'événement: %s",
                    $invitation->getId(),
                    $invitation->getEmail(),
                    $event->getCategory()
                ));
            }
        }
        
        // Sauvegarder les changements si nécessaire
        if ($expiredCount > 0) {
            $entityManager->flush();
            $this->addFlash('info', "{$expiredCount} invitation(s) automatiquement marquée(s) comme expirée(s).");
        }
        
        // Récupérer les participations pour chaque invitation
        $participations = [];
        foreach ($invitations as $invitation) {
            // Chercher l'utilisateur par email
            $user = $entityManager->getRepository(User::class)
                ->findOneBy(['email' => $invitation->getEmail()]);
            
            if ($user) {
                // Chercher la participation de cet utilisateur pour cet événement
                $participation = $entityManager->getRepository(Participation::class)
                    ->findOneBy([
                        'user' => $user,
                        'event' => $event
                    ]);
                
                if ($participation) {
                    $participations[$invitation->getId()] = $participation;
                }
            }
        }

        return $this->render('invitation/index.html.twig', [
            'event' => $event,
            'invitations' => $invitations,
            'participations' => $participations
        ]);
    }

    #[Route('/invitations/force-expiration', name: 'invitation_force_expiration', methods: ['GET'])]
    public function forceExpiration(
        InvitationRepository $invitationRepository,
        EntityManagerInterface $entityManager
    ): Response {
        // Récupérer toutes les invitations en attente
        $invitations = $invitationRepository->findBy(['status' => 'pending']);
        
        $expiredCount = 0;
        foreach ($invitations as $invitation) {
            $event = $invitation->getEvent();
            // Forcer l'expiration avec un délai d'un jour
            if ($invitation->checkAndMarkAsExpired(1)) {
                $expiredCount++;
                error_log(sprintf(
                    "Invitation expirée - ID: %d, Email: %s, Type d'événement: %s",
                    $invitation->getId(),
                    $invitation->getEmail(),
                    $event ? $event->getCategory() : 'inconnu'
                ));
            }
        }
        
        if ($expiredCount > 0) {
            $entityManager->flush();
            $this->addFlash('success', "{$expiredCount} invitation(s) marquée(s) comme expirée(s).");
        } else {
            $this->addFlash('info', "Aucune invitation n'a été expirée.");
        }
        
        return $this->redirectToRoute('event_list');
    }

    #[Route('/invitations/{id}/cancel', name: 'invitation_cancel', methods: ['GET'])]
    public function cancel(
        int $id,
        EntityManagerInterface $entityManager,
        GlobalNotificationService $globalNotificationService,
        InvitationRepository $invitationRepository
    ): Response {
        $invitation = $invitationRepository->find($id);
        
        if (!$invitation) {
            throw $this->createNotFoundException('Invitation non trouvée.');
        }
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

        // Notification globale pour la suppression d'invitation
        try {
            $globalNotificationService->notifyPlatformModification('supprimé', 'invitation', $invitation);
        } catch (\Exception $e) {
            error_log('Erreur notification globale suppression invitation: ' . $e->getMessage());
        }

        $this->addFlash('success', 'L\'invitation a été annulée avec succès.');
        return $this->redirectToRoute('invitation_index', ['eventId' => $event->getId()]);
    }

    private function sendInvitationEmail(Invitation $invitation, MailerInterface $mailer): void
    {
        try {
            $email = (new Email())
                ->from('nadiabalaazi@gmail.com')
                ->to($invitation->getEmail())
                ->subject('Invitation à l\'événement: ' . $invitation->getEvent()->getTitle())
                ->html($this->renderView('emails/event_invitation.html.twig', [
                    'invitation' => $invitation,
                ]));

            // Ajouter des logs détaillés pour le débogage
            error_log("Tentative d'envoi d'email à {$invitation->getEmail()} pour l'événement {$invitation->getEvent()->getTitle()}");
            error_log("Paramètres email: From: nadiabalaazi@gmail.com, To: {$invitation->getEmail()}");
            
            $mailer->send($email);
            error_log("Email envoyé avec succès à {$invitation->getEmail()}");
        } catch (\Exception $e) {
            error_log("ERREUR d'envoi d'email à {$invitation->getEmail()}: " . $e->getMessage());
            error_log("Trace: " . $e->getTraceAsString());
            throw $e; // Relancer l'exception pour la gestion d'erreur au niveau supérieur
        }
    }
}