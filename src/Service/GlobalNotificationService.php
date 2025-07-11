<?php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Salle;
use App\Entity\Invitation;
use App\Entity\Document;
use App\Entity\Participation;
use App\Entity\CollaborativeNote;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Twig\Environment;

class GlobalNotificationService
{
    private const ADMIN_EMAIL = 'eventhub.contact.tunisie@gmail.com';
    private const FROM_EMAIL = 'nadiabalaazi@gmail.com';
    
    private MailerInterface $mailer;
    private UserRepository $userRepository;
    private TokenStorageInterface $tokenStorage;
    private Environment $twig;

    public function __construct(
        MailerInterface $mailer,
        UserRepository $userRepository,
        TokenStorageInterface $tokenStorage,
        Environment $twig
    ) {
        $this->mailer = $mailer;
        $this->userRepository = $userRepository;
        $this->tokenStorage = $tokenStorage;
        $this->twig = $twig;
    }

    /**
     * Notifie toutes les modifications importantes sur la plateforme
     */
    public function notifyPlatformModification(string $action, string $entityType, $entity, ?User $user = null): void
    {
        $currentUser = $user ?? $this->getCurrentUser();
        
        if (!$currentUser) {
            return;
        }

        // Préparer les informations de notification
        $notificationData = $this->prepareNotificationData($action, $entityType, $entity, $currentUser);
        
        // Envoyer à l'administrateur
        $this->sendAdminNotification($notificationData);
        
        // Envoyer aux utilisateurs concernés selon leurs préférences
        $this->sendUserNotifications($notificationData, $entity);
    }

    /**
     * Prépare les données de notification selon le type d'action et d'entité
     */
    private function prepareNotificationData(string $action, string $entityType, $entity, User $user): array
    {
        $entityName = $this->getEntityName($entityType, $entity);
        $entityDetails = $this->getEntityDetails($entityType, $entity);
        
        return [
            'action' => $action,
            'entityType' => $entityType,
            'entityName' => $entityName,
            'entityDetails' => $entityDetails,
            'user' => $user,
            'timestamp' => new \DateTime(),
            'icon' => $this->getActionIcon($action)
        ];
    }

    /**
     * Obtient le nom de l'entité selon son type
     */
    private function getEntityName(string $entityType, $entity): string
    {
        switch ($entityType) {
            case 'event':
                return $entity->getTitle();
            case 'salle':
                return $entity->getNom();
            case 'user':
                return $entity->getFullName();
            case 'document':
                return $entity->getName() ?? 'Document';
            case 'invitation':
                return $entity->getEvent() ? $entity->getEvent()->getTitle() : 'Invitation';
            case 'participation':
                return $entity->getEvent() ? $entity->getEvent()->getTitle() : 'Participation';
            case 'collaborative_note':
                return $entity->getTitle() ?? 'Note collaborative';
            default:
                return ucfirst($entityType);
        }
    }

    /**
     * Obtient les détails de l'entité selon son type
     */
    private function getEntityDetails(string $entityType, $entity): array
    {
        switch ($entityType) {
            case 'event':
                return [
                    'date' => $entity->getDateHeure() ? $entity->getDateHeure()->format('d/m/Y H:i') : null,
                    'salle' => $entity->getSalle() ? $entity->getSalle()->getNom() : null,
                    'organizer' => $entity->getOrganizer() ? $entity->getOrganizer()->getFullName() : null
                ];
            case 'salle':
                return [
                    'capacity' => $entity->getCapacite(),
                    'location' => $entity->getLocalisation()
                ];
            case 'user':
                return [
                    'email' => $entity->getEmail(),
                    'department' => $entity->getDepartement() ? $entity->getDepartement()->getNom() : null,
                    'roles' => implode(', ', $entity->getRoles())
                ];
            default:
                return [];
        }
    }

    /**
     * Obtient l'icône selon l'action
     */
    private function getActionIcon(string $action): string
    {
        return match ($action) {
            'créé', 'création' => '🎉',
            'modifié', 'modification' => '✏️',
            'supprimé', 'suppression' => '🗑️',
            'annulé', 'annulation' => '❌',
            'accepté', 'acceptation' => '✅',
            'refusé', 'refus' => '❌',
            default => '📝'
        };
    }

    /**
     * Envoie une notification à l'administrateur
     */
    private function sendAdminNotification(array $data): void
    {
        try {
            $subject = sprintf(
                '%s %s %s - %s',
                $data['icon'],
                ucfirst($data['entityType']),
                $data['action'],
                $data['entityName']
            );

            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to(self::ADMIN_EMAIL)
                ->subject($subject)
                ->html($this->renderAdminNotificationTemplate($data));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi notification admin: ' . $e->getMessage());
        }
    }

    /**
     * Envoie des notifications aux utilisateurs concernés
     */
    private function sendUserNotifications(array $data, $entity): void
    {
        $usersToNotify = $this->getUsersToNotify($data['entityType'], $entity);
        
        foreach ($usersToNotify as $user) {
            if ($user->isNotifyByEmail() && $user->getEmail()) {
                $this->sendUserNotification($user, $data);
            }
        }
    }

    /**
     * Détermine quels utilisateurs doivent être notifiés
     */
    private function getUsersToNotify(string $entityType, $entity): array
    {
        $users = [];
        
        switch ($entityType) {
            case 'event':
                // Notifier les participants
                foreach ($entity->getParticipations() as $participation) {
                    if ($participation->getUser()) {
                        $users[] = $participation->getUser();
                    }
                }
                break;
                
            case 'salle':
                // Notifier les organisateurs d'événements dans cette salle
                // (Optionnel selon vos besoins)
                break;
                
            case 'invitation':
                // Notifier l'utilisateur invité
                if ($entity->getParticipant()) {
                    $users[] = $entity->getParticipant();
                }
                break;
                
            default:
                // Pour les autres types, vous pouvez ajouter votre logique
                break;
        }
        
        return array_unique($users);
    }

    /**
     * Envoie une notification à un utilisateur spécifique
     */
    private function sendUserNotification(User $user, array $data): void
    {
        try {
            $subject = sprintf(
                '%s Modification sur EventHub - %s',
                $data['icon'],
                $data['entityName']
            );

            $email = (new Email())
                ->from(self::FROM_EMAIL)
                ->to($user->getEmail())
                ->subject($subject)
                ->html($this->renderUserNotificationTemplate($user, $data));

            $this->mailer->send($email);
        } catch (\Exception $e) {
            error_log('Erreur envoi notification utilisateur: ' . $e->getMessage());
        }
    }

    /**
     * Template HTML pour les notifications administrateur
     */
    private function renderAdminNotificationTemplate(array $data): string
    {
        return $this->twig->render('emails/global_notification.html.twig', $data);
    }

    /**
     * Template HTML pour les notifications utilisateur
     */
    private function renderUserNotificationTemplate(User $user, array $data): string
    {
        $data['recipient'] = $user;
        return $this->twig->render('emails/global_notification.html.twig', $data);
    }

    /**
     * Obtient l'utilisateur actuel
     */
    private function getCurrentUser(): ?User
    {
        $token = $this->tokenStorage->getToken();
        if (!$token) {
            return null;
        }

        $user = $token->getUser();
        return $user instanceof User ? $user : null;
    }
}