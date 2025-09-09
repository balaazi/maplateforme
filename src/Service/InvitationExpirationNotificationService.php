<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Psr\Log\LoggerInterface;

class InvitationExpirationNotificationService
{
    public function __construct(
        private MailerInterface $mailer,
        private UrlGeneratorInterface $urlGenerator,
        private LoggerInterface $logger
    ) {}

    /**
     * Envoie un email de notification d'expiration pour une invitation
     * DÉSACTIVÉ - Aucun email n'est envoyé
     */
    public function sendExpirationNotification(Invitation $invitation): void
    {
        // DÉSACTIVÉ - Aucun email d'expiration n'est envoyé
        // Le statut est mis à jour automatiquement sans notification
        
        $this->logger->info('Notification d\'expiration désactivée - Aucun email envoyé', [
            'invitation_id' => $invitation->getId(),
            'email' => $invitation->getEmail(),
            'event_title' => $invitation->getEvent()?->getTitle() ?? 'N/A'
        ]);
    }

    /**
     * Envoie des notifications d'expiration pour plusieurs invitations
     * DÉSACTIVÉ - Aucun email n'est envoyé
     */
    public function sendExpirationNotifications(array $invitations): int
    {
        // DÉSACTIVÉ - Aucun email d'expiration n'est envoyé
        // Le statut est mis à jour automatiquement sans notification
        
        $this->logger->info('Notifications d\'expiration désactivées - Aucun email envoyé', [
            'count' => count($invitations)
        ]);
        
        return 0; // Aucun email envoyé
    }

    /**
     * Rend le template HTML pour l'email d'expiration
     * DÉSACTIVÉ - Non utilisé
     */
    private function renderExpirationEmailTemplate(Invitation $invitation, $event): string
    {
        // DÉSACTIVÉ - Template non utilisé
        return '';
    }
}

