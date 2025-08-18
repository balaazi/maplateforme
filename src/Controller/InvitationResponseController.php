<?php
// src/Controller/InvitationResponseController.php

namespace App\Controller;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\Participation;
use App\Entity\User;
use App\Service\ScheduleConflictService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use App\Service\EmailService;
use Psr\Log\LoggerInterface;

#[Route('/respond/invitation')]
class InvitationResponseController extends AbstractController
{
    public function __construct(
        private LoggerInterface $logger,
        private ScheduleConflictService $scheduleConflictService
    ) {}

    #[Route('/{token}/{response}', name: 'invitation_respond', methods: ['GET'])]
    public function respond(string $token, string $response, EntityManagerInterface $entityManager): Response
    {
        $this->logger->info('Début du traitement de réponse à l\'invitation', [
            'token' => substr($token, 0, 8) . '...',
            'response' => $response
        ]);

        // Trouver l'invitation
        $invitation = $entityManager->getRepository(Invitation::class)
            ->findOneBy(['token' => $token]);

        if (!$invitation) {
            $this->logger->error('Invitation non trouvée', ['token' => substr($token, 0, 8) . '...']);
            throw $this->createNotFoundException('Invitation non trouvée');
        }

        // Bloquer la réponse si la date de l'événement est dépassée
        $event = $invitation->getEvent();
        if ($event && $event->getDateHeure()) {
            $now = new \DateTime('now', new \DateTimeZone('Europe/Paris'));
            $eventStart = $event->getDateHeure();
            $eventEnd = (clone $eventStart)->modify('+' . (int) $event->getDuree() . ' minutes');

            if ($now > $eventEnd) {
                $this->logger->info("Réponse bloquée: événement dépassé", [
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'event_end' => $eventEnd->format('Y-m-d H:i:s'),
                    'now' => $now->format('Y-m-d H:i:s'),
                    'response' => $response,
                ]);

                return $this->render('invitation/expired.html.twig', [
                    'invitation' => $invitation,
                    'response' => $response,
                ]);
            }
        }

        // Vérifier si l'invitation n'a pas déjà été traitée
        if ($invitation->getStatus() !== 'pending') {
            $this->logger->info('Invitation déjà traitée', [
                'invitation_id' => $invitation->getId(),
                'current_status' => $invitation->getStatus(),
                'new_response' => $response
            ]);
            // Permettre quand même de voir la page de confirmation
        }

        // Mettre à jour l'invitation
        $invitation->setStatus($response)
                ->setUpdatedAt(new \DateTime());

        $this->logger->info('Statut d\'invitation mis à jour', [
            'invitation_id' => $invitation->getId(),
            'event_title' => $invitation->getEvent()->getTitle(),
            'email' => $invitation->getEmail(),
            'response' => $response
        ]);

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

        // Mettre à jour l'état de participation selon la réponse
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
                
                // Afficher une page d'erreur avec les détails du conflit
                return $this->render('invitation/conflict.html.twig', [
                    'invitation' => $invitation,
                    'newEvent' => $invitation->getEvent(),
                    'conflictingEvent' => $conflict['conflictingEvent'],
                    'user' => $user,
                    'conflictMessage' => $conflict['message']
                ]);
            }
            
            $participation->setInvitationStatus('accepté');
            $participation->setIsPresent(false);  // Présence non validée automatiquement
            
            $this->logger->info('Participation confirmée - présence à valider séparément', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId(),
                'event_title' => $invitation->getEvent()->getTitle(),
                'user_email' => $user->getEmail()
            ]);
            
        } elseif ($response === 'declined') {
            $participation->setInvitationStatus('refusé');
            $participation->setIsPresent(false);
            
            $this->logger->info('Participation refusée - utilisateur marqué comme absent', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId(),
                'event_title' => $invitation->getEvent()->getTitle(),
                'user_email' => $user->getEmail()
            ]);
        } else {
            $participation->setInvitationStatus('en_attente');
            $participation->setIsPresent(false);
            
            $this->logger->info('Participation en attente', [
                'user_id' => $user->getId(),
                'event_id' => $invitation->getEvent()->getId()
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
