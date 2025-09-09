<?php
// src/Controller/InvitationResponseController.php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\Participation;
use App\Entity\User;
use App\Service\ScheduleConflictService;
use App\Enum\InvitationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailService;
use App\Service\InvitationExpirationService;
use Psr\Log\LoggerInterface;

#[Route('/respond/invitation')]
class InvitationResponseController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ScheduleConflictService $scheduleConflictService,
        private InvitationExpirationService $expirationService
    ) {}

    #[Route('/{token}/{response}', name: 'invitation_respond', methods: ['GET'])]
    public function respond(string $token, string $response, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Début du traitement de réponse à l\'invitation', [
            'token' => substr($token, 0, 8) . '...',
            'response' => $response
        ]);

        // Valider la réponse
        if (!in_array($response, ['accepted', 'declined'])) {
            $this->logger->error('Réponse invalide', ['response' => $response]);
            throw new \InvalidArgumentException('Réponse invalide');
        }

        // Trouver l'invitation
        $invitation = $entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            $this->logger->error('Invitation non trouvée', ['token' => substr($token, 0, 8) . '...']);
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        // Vérifier et expirer automatiquement l'invitation si elle est expirée
        if ($invitation->checkAndMarkAsExpired(30)) {
            $entityManager->flush();
            
            $this->logger->info("Invitation automatiquement expirée lors de la réponse", [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'response' => $response
            ]);

            return $this->render('invitation/expired.html.twig', [
                'invitation' => $invitation,
                'response' => $response,
            ]);
        }

        // Bloquer la réponse si la date de l'événement est dépassée
        $event = $invitation->getEvent();
        if ($event && $event->getDateHeure()) {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $eventStart = $event->getDateHeure();
            $eventEnd = (clone $eventStart)->modify('+' . (int) $event->getDuree() . ' minutes');

            if ($now > $eventEnd) {
                // Marquer l'invitation comme expirée
                $invitation->setStatus(InvitationStatus::EXPIRED->value);
                $invitation->setUpdatedAt(new \DateTime());
                $entityManager->flush();
                
                $this->logger->info("Réponse bloquée et invitation expirée: événement dépassé", [
                    'invitation_id' => $invitation->getId(),
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'event_end' => $eventEnd->format('Y-m-d H:i:s'),
                    'now' => $now->format('Y-m-d H:i:s'),
                    'response' => $response,
                ]);

                return $this->render('invitation/expired.html.twig', [
                    'invitation' => $invitation,
                    'response' => $response,
                    'event_passed' => true
                ]);
            }
        }

        // Vérifier si l'invitation n'a pas déjà été traitée
        if ($invitation->getStatus() !== InvitationStatus::PENDING->value) {
            $this->logger->info('Invitation déjà traitée', [
                'invitation_id' => $invitation->getId(),
                'current_status' => $invitation->getStatus(),
                'new_response' => $response
            ]);
            // Permettre quand même de voir la page de confirmation
        }

        // Trouver ou créer l'utilisateur correspondant à l'invitation
        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['email' => $invitation->getEmail()]);

        $isNewUser = false;
        if (!$user) {
            // Créer un nouvel utilisateur
            $user = new User();
            $user->setEmail($invitation->getEmail());
            $user->setNom($invitation->getName() ?? 'Utilisateur');
            $user->setPrenom('Nouveau');
            $user->setTelephone('Non renseigné');
            $user->setRoles(['ROLE_PARTICIPANT']);
            
            // Générer un mot de passe temporaire
            $tempPassword = bin2hex(random_bytes(8));
            $user->setPassword($tempPassword); // Note: devrait être hashé en production
            
            $entityManager->persist($user);
            $isNewUser = true;
            
            $this->logger->info('Nouvel utilisateur créé', [
                'email' => $invitation->getEmail(),
                'user_id' => $user->getId()
            ]);
        }

        // Vérifier si la participation existe déjà
        $participation = $entityManager->getRepository(Participation::class)
            ->findOneBy([
                'user' => $user,
                'event' => $invitation->getEvent()
            ]);

        if (!$participation) {
            $participation = new Participation();
            $participation->setUser($user);
            $participation->setEvent($invitation->getEvent());
            $participation->setCreatedAt(new \DateTime());
            $entityManager->persist($participation);
            
            $this->logger->info('Nouvelle participation créée', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId()
            ]);
        }

        // Déterminer le statut final selon la réponse
        $finalStatus = null;
        
        if ($response === 'accepted') {
            // Vérifier s'il y a un conflit d'horaires avant d'accepter
            $conflict = $this->scheduleConflictService->checkScheduleConflict($user, $invitation->getEvent());
            
            if ($conflict) {
                $this->logger->warning('Conflit d\'horaires détecté', [
                    'user_id' => $user->getId(),
                    'new_event_id' => $invitation->getEvent()->getId(),
                    'new_event_title' => $invitation->getEvent()->getTitle(),
                    'conflicting_event_id' => $conflict['conflictingEvent']->getId(),
                    'conflicting_event_title' => $conflict['conflictingEvent']->getTitle(),
                    'user_email' => $user->getEmail()
                ]);
                
                // Marquer l'invitation et la participation avec le statut CONFLICT
                $finalStatus = InvitationStatus::CONFLICT->value;
                
                // Mettre à jour l'invitation avec le statut CONFLICT
                $invitation->setStatus($finalStatus);
                $invitation->setUpdatedAt(new \DateTime());
                
                // Mettre à jour la participation avec le statut CONFLICT
                $participation->setInvitationStatus($finalStatus);
                
                // Sauvegarder les changements
                try {
                    $entityManager->flush();
                    $this->logger->info('Statut CONFLICT sauvegardé en base de données', [
                        'invitation_id' => $invitation->getId(),
                        'participation_id' => $participation->getId(),
                        'user_email' => $user->getEmail()
                    ]);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur lors de la sauvegarde du statut CONFLICT', [
                        'error' => $e->getMessage()
                    ]);
                    throw $e;
                }
                
                // Afficher une page d'erreur avec les détails du conflit
                return $this->render('invitation/conflict.html.twig', [
                    'invitation' => $invitation,
                    'newEvent' => $invitation->getEvent(),
                    'conflictingEvent' => $conflict['conflictingEvent'],
                    'user' => $user,
                    'conflictMessage' => $conflict['message']
                ]);
            }
            
            $finalStatus = InvitationStatus::ACCEPTED->value;
            $participation->setIsPresent(false);  // Présence non validée automatiquement
            
            $this->logger->info('Participation confirmée - présence à valider séparément', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId(),
                'event_title' => $invitation->getEvent()->getTitle(),
                'user_email' => $user->getEmail()
            ]);
            
        } elseif ($response === 'declined') {
            $finalStatus = InvitationStatus::DECLINED->value;
            $participation->setIsPresent(false);
            
            $this->logger->info('Participation refusée - utilisateur marqué comme absent', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId(),
                'event_title' => $invitation->getEvent()->getTitle(),
                'user_email' => $user->getEmail()
            ]);
        }

        // Mettre à jour l'invitation avec le statut final
        if ($finalStatus) {
            $invitation->setStatus($finalStatus);
            $invitation->setUpdatedAt(new \DateTime());
            
            $this->logger->info('Statut d\'invitation mis à jour', [
                'invitation_id' => $invitation->getId(),
                'event_title' => $invitation->getEvent()->getTitle(),
                'email' => $invitation->getEmail(),
                'new_status' => $finalStatus
            ]);
        }

        // Mettre à jour la participation avec le même statut
        if ($finalStatus) {
            $participation->setInvitationStatus($finalStatus);
            
            $this->logger->info('Statut de participation mis à jour', [
                'participation_id' => $participation->getId(),
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId(),
                'new_status' => $finalStatus
            ]);
        }

        // Sauvegarder tous les changements
        try {
            $entityManager->flush();
            $this->logger->info('Toutes les modifications sauvegardées avec succès');
        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la sauvegarde', [
                'error' => $e->getMessage()
            ]);
            throw $e;
        }

        return $this->render('invitation/response.html.twig', [
            'response' => $response,
            'invitation' => $invitation,
            'isNewUser' => $isNewUser,
            'participation' => $participation,
        ]);
    }
}
