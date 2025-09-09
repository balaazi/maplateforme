<?php

namespace App\Service;

use App\Entity\Invitation;
use App\Enum\InvitationStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class InvitationExpirationNotifier
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer,
        private LoggerInterface $logger,
        private UrlGeneratorInterface $urlGenerator
    ) {}

    public function notifyExpiration(Invitation $invitation): void
    {
        try {
            // 1. Mettre à jour le statut
            if ($invitation->getStatus() !== InvitationStatus::EXPIRED->value) {
                $invitation->setStatus(InvitationStatus::EXPIRED->value);
                $invitation->setUpdatedAt(new \DateTime());
                $this->entityManager->flush();

                $this->logger->info('Statut de l\'invitation mis à jour à expiré', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail()
                ]);
            }

            // 2. Vérifier les préférences de notification de l'utilisateur
            $user = $this->entityManager->getRepository('App\Entity\User')
                ->findOneBy(['email' => $invitation->getEmail()]);

            // N'envoyer l'e-mail que si l'utilisateur a activé les notifications par e-mail
            if ($user && $user->isNotifyByEmail()) {
                $email = (new Email())
                    ->from('nadiabalaazi@gmail.com')
                    ->to($invitation->getEmail())
                    ->subject('Invitation expirée - ' . $invitation->getEvent()?->getTitle())
                    ->html($this->renderExpirationEmail($invitation));

                $this->mailer->send($email);
                
                $this->logger->info('E-mail d\'expiration envoyé', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail()
                ]);
            } else {
                $this->logger->info('E-mail d\'expiration non envoyé - Notifications désactivées', [
                    'invitation_id' => $invitation->getId(),
                    'email' => $invitation->getEmail()
                ]);
            }

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors de la notification d\'expiration', [
                'invitation_id' => $invitation->getId(),
                'error' => $e->getMessage()
            ]);
            throw $e;
        }
    }

    private function renderExpirationEmail(Invitation $invitation): string
    {
        $eventTitle = $invitation->getEvent()?->getTitle() ?? 'Événement';
        $disableNotificationsUrl = $this->urlGenerator->generate('disable_email_notifications', [], 
            UrlGeneratorInterface::ABSOLUTE_URL);
        $preferencesUrl = $this->urlGenerator->generate('user_preferences', [], 
            UrlGeneratorInterface::ABSOLUTE_URL);
        
        return "
            <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
                <h2 style='color: #333;'>Invitation expirée</h2>
                <p>Bonjour {$invitation->getName()},</p>
                <p>L'invitation pour l'événement \"{$eventTitle}\" a expiré.</p>
                <p>Si vous souhaitez toujours participer à cet événement, veuillez contacter l'organisateur pour obtenir une nouvelle invitation.</p>
                <div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0;'><strong>Détails de l'événement :</strong></p>
                    <p style='margin: 5px 0;'>Titre : {$eventTitle}</p>
                    <p style='margin: 5px 0;'>Date de création : {$invitation->getCreatedAt()->format('d/m/Y H:i')}</p>
                </div>
                <div style='margin-top: 20px; padding: 15px; background-color: #f8f9fa; border-radius: 5px;'>
                    <p style='margin: 0;'><strong>Gérer vos notifications :</strong></p>
                    <p style='margin: 5px 0;'>
                        <a href='{$disableNotificationsUrl}' style='color: #dc3545; text-decoration: none;'>
                            Ne plus recevoir ces notifications
                        </a>
                    </p>
                    <p style='margin: 5px 0;'>
                        <a href='{$preferencesUrl}' style='color: #0d6efd; text-decoration: none;'>
                            Gérer mes préférences de notification
                        </a>
                    </p>
                </div>
                <p style='margin-top: 20px; color: #666;'>Ceci est un message automatique, merci de ne pas y répondre.</p>
            </div>
        ";
    }
}
