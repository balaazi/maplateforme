<?php
// src/Service/EventNotificationService.php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Repository\UserRepository;
use App\Service\NotificationService;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
use Psr\Log\LoggerInterface;

class EventNotificationService
{
private $mailer;
private $userRepository;
private $notificationService;
    private $logger;

    public function __construct(
        MailerInterface $mailer, 
        UserRepository $userRepository, 
        NotificationService $notificationService,
        LoggerInterface $logger
    ) {
$this->mailer = $mailer;
$this->userRepository = $userRepository;
$this->notificationService = $notificationService;
        $this->logger = $logger;
}

// Cette méthode envoie une notification de mise à jour de l'événement
public function sendEventUpdateNotification(Event $event): void
{
        $sentCount = 0;
        $errors = [];

        // 1. Notification à l'organisateur (NOUVELLE FONCTIONNALITÉ)
        $organizer = $event->getOrganizer();
        if ($organizer && $organizer->getEmail()) {
            // ✅ CRÉER TOUJOURS LA NOTIFICATION EN BASE DE DONNÉES
            try {
                $this->notificationService->createEventUpdateNotification($organizer, $event);
            } catch (\Exception $e) {
                $this->logger->error('Erreur création notification organisateur en base', [
                    'event_id' => $event->getId(),
                    'organizer_email' => $organizer->getEmail(),
                    'error' => $e->getMessage()
                ]);
            }

            // Envoyer l'email seulement si activé
            if ($organizer->isNotifyByEmail()) {
                try {
                    $email = (new Email())
                        ->from('nadiabalaazi@gmail.com')
                        ->to($organizer->getEmail())
                        ->subject('✏️ Confirmation : Votre événement a été modifié - ' . $event->getTitle())
                        ->html($this->renderOrganizerUpdateTemplate($event, $organizer));

                    $this->mailer->send($email);
                    $sentCount++;
                    
                    $this->logger->info('Email de modification envoyé à l\'organisateur', [
                        'event_id' => $event->getId(),
                        'organizer_email' => $organizer->getEmail()
                    ]);
                } catch (\Exception $e) {
                    $errors[] = sprintf('Erreur envoi email organisateur %s: %s', $organizer->getEmail(), $e->getMessage());
                    $this->logger->error('Erreur envoi email organisateur', [
                        'event_id' => $event->getId(),
                        'organizer_email' => $organizer->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 2. Envoyer aux participants de l'événement
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user && $user->getEmail()) {
                // ✅ CRÉER TOUJOURS LA NOTIFICATION EN BASE DE DONNÉES
                try {
                    $this->notificationService->createEventUpdateNotification($user, $event);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur création notification participant en base', [
                        'event_id' => $event->getId(),
                        'participant_email' => $user->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }

                // Envoyer l'email seulement si activé
                if ($user->isNotifyByEmail()) {
                    try {
                        $email = (new Email())
                            ->from('nadiabalaazi@gmail.com')
                            ->to($user->getEmail())
                            ->subject('🔔 Événement modifié : ' . $event->getTitle())
                            ->html($this->renderUpdateTemplate($event, $user));

                        $this->mailer->send($email);
                        $sentCount++;
                        
                        $this->logger->info('Email de modification envoyé au participant', [
                            'event_id' => $event->getId(),
                            'participant_email' => $user->getEmail()
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = sprintf('Erreur envoi email participant %s: %s', $user->getEmail(), $e->getMessage());
                        $this->logger->error('Erreur envoi email participant', [
                            'event_id' => $event->getId(),
                            'participant_email' => $user->getEmail(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // 3. Envoyer aussi aux invités (invitations) - EMAIL SEULEMENT
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getEmail()) {
                try {
                    $participantName = $invitation->getParticipant() ? 
                        $invitation->getParticipant()->getFullName() : 'Invité(e)';

                    $email = (new Email())
                            ->from('nadiabalaazi@gmail.com')
                        ->to($invitation->getEmail())
                        ->subject('🔔 Événement modifié : ' . $event->getTitle())
                        ->html($this->renderUpdateTemplateForInvitation($event, $participantName));

                    $this->mailer->send($email);
                    $sentCount++;
                    
                    $this->logger->info('Email de modification envoyé à l\'invité', [
                        'event_id' => $event->getId(),
                        'invite_email' => $invitation->getEmail()
                    ]);
                } catch (\Exception $e) {
                    $errors[] = sprintf('Erreur envoi email invité %s: %s', $invitation->getEmail(), $e->getMessage());
                    $this->logger->error('Erreur envoi email invité', [
                        'event_id' => $event->getId(),
                        'invite_email' => $invitation->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Log du résumé
        $this->logger->info('Notifications de modification envoyées', [
            'event_id' => $event->getId(),
            'sent_count' => $sentCount,
            'error_count' => count($errors)
        ]);

        if (!empty($errors)) {
            $this->logger->warning('Erreurs lors de l\'envoi des notifications de modification', [
                'event_id' => $event->getId(),
                'errors' => $errors
            ]);
        }
    }

public function sendEventCancelNotification(Event $event): void
{
        $sentCount = 0;
        $errors = [];

        // 1. Notification à l'organisateur (NOUVELLE FONCTIONNALITÉ)
        $organizer = $event->getOrganizer();
        if ($organizer && $organizer->getEmail()) {
            // ✅ CRÉER TOUJOURS LA NOTIFICATION EN BASE DE DONNÉES
            try {
                $this->notificationService->createEventCancelNotification($organizer, $event);
            } catch (\Exception $e) {
                $this->logger->error('Erreur création notification annulation organisateur en base', [
                    'event_id' => $event->getId(),
                    'organizer_email' => $organizer->getEmail(),
                    'error' => $e->getMessage()
                ]);
            }

            // Envoyer l'email seulement si activé
            if ($organizer->isNotifyByEmail()) {
                try {
                    $email = (new Email())
                        ->from('nadiabalaazi@gmail.com')
                        ->to($organizer->getEmail())
                        ->subject('❌ Confirmation : Votre événement a été annulé - ' . $event->getTitle())
                        ->html($this->renderOrganizerCancelTemplate($event, $organizer));

                    $this->mailer->send($email);
                    $sentCount++;
                    
                    $this->logger->info('Email d\'annulation envoyé à l\'organisateur', [
                        'event_id' => $event->getId(),
                        'organizer_email' => $organizer->getEmail()
                    ]);
                } catch (\Exception $e) {
                    $errors[] = sprintf('Erreur envoi email organisateur %s: %s', $organizer->getEmail(), $e->getMessage());
                    $this->logger->error('Erreur envoi email organisateur', [
                        'event_id' => $event->getId(),
                        'organizer_email' => $organizer->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // 2. Envoyer aux participants de l'événement
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user && $user->getEmail()) {
                // ✅ CRÉER TOUJOURS LA NOTIFICATION EN BASE DE DONNÉES
                try {
                    $this->notificationService->createEventCancelNotification($user, $event);
                } catch (\Exception $e) {
                    $this->logger->error('Erreur création notification annulation participant en base', [
                        'event_id' => $event->getId(),
                        'participant_email' => $user->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }

                // Envoyer l'email seulement si activé
                if ($user->isNotifyByEmail()) {
                    try {
                        $email = (new Email())
                            ->from('nadiabalaazi@gmail.com')
                            ->to($user->getEmail())
                            ->subject('❌ Événement annulé : ' . $event->getTitle())
                            ->html($this->renderCancelTemplate($event, $user));

                        $this->mailer->send($email);
                        $sentCount++;
                        
                        $this->logger->info('Email d\'annulation envoyé au participant', [
                            'event_id' => $event->getId(),
                            'participant_email' => $user->getEmail()
                        ]);
                    } catch (\Exception $e) {
                        $errors[] = sprintf('Erreur envoi email participant %s: %s', $user->getEmail(), $e->getMessage());
                        $this->logger->error('Erreur envoi email participant', [
                            'event_id' => $event->getId(),
                            'participant_email' => $user->getEmail(),
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }

        // 3. Envoyer aussi aux invités (invitations) - EMAIL SEULEMENT
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getEmail()) {
                try {
                    $participantName = $invitation->getParticipant() ? 
                        $invitation->getParticipant()->getFullName() : 'Invité(e)';

                    $email = (new Email())
                            ->from('nadiabalaazi@gmail.com')
                        ->to($invitation->getEmail())
                        ->subject('❌ Événement annulé : ' . $event->getTitle())
                        ->html($this->renderCancelTemplateForInvitation($event, $participantName));

                    $this->mailer->send($email);
                    $sentCount++;
                    
                    $this->logger->info('Email d\'annulation envoyé à l\'invité', [
                        'event_id' => $event->getId(),
                        'invite_email' => $invitation->getEmail()
                    ]);
                } catch (\Exception $e) {
                    $errors[] = sprintf('Erreur envoi email invité %s: %s', $invitation->getEmail(), $e->getMessage());
                    $this->logger->error('Erreur envoi email invité', [
                        'event_id' => $event->getId(),
                        'invite_email' => $invitation->getEmail(),
                        'error' => $e->getMessage()
                    ]);
                }
            }
        }

        // Log du résumé
        $this->logger->info('Notifications d\'annulation envoyées', [
            'event_id' => $event->getId(),
            'sent_count' => $sentCount,
            'error_count' => count($errors)
        ]);

        if (!empty($errors)) {
            $this->logger->warning('Erreurs lors de l\'envoi des notifications d\'annulation', [
                'event_id' => $event->getId(),
                'errors' => $errors
            ]);
        }
    }

    /**
     * NOUVEAU : Template HTML pour les notifications de modification à l'organisateur
     */
    private function renderOrganizerUpdateTemplate(Event $event, $organizer): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #28a745 0%, #20c997 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">✏️ Modification Confirmée</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($organizer->getFullName()) . '</strong>,</p>
                    
                    <p>Votre événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> a été <strong>modifié avec succès</strong>.</p>
                    
                    <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #0c5460;">Nouvelles informations de votre événement :</h3>
                        <p><strong>📅 Date et heure :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                        <p><strong>📝 Description :</strong> ' . htmlspecialchars($event->getDescription() ?? 'Aucune description') . '</p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;">
                        <p><strong>ℹ️ Information :</strong> Tous les participants et invités ont été automatiquement notifiés de ces modifications.</p>
                    </div>
                    
                    <p>Merci d\'utiliser EventHub pour organiser vos événements.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
    }

    /**
     * NOUVEAU : Template HTML pour les notifications d'annulation à l'organisateur
     */
    private function renderOrganizerCancelTemplate(Event $event, $organizer): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #dc3545 0%, #c82333 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">❌ Annulation Confirmée</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($organizer->getFullName()) . '</strong>,</p>
                    
                    <p>Votre événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> a été <strong>annulé avec succès</strong>.</p>
                    
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #721c24;">Détails de l\'événement annulé :</h3>
                        <p><strong>📅 Date prévue :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                        <p><strong>📝 Description :</strong> ' . htmlspecialchars($event->getDescription() ?? 'Aucune description') . '</p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #ffc107; margin: 20px 0;">
                        <p><strong>ℹ️ Information :</strong> Tous les participants et invités ont été automatiquement notifiés de cette annulation.</p>
                    </div>
                    
                    <p>Merci d\'utiliser EventHub pour organiser vos événements.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
}

    /**
     * Template HTML pour les notifications de modification aux participants
     */
    private function renderUpdateTemplate(Event $event, $user): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">🔔 Événement Modifié</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($user->getFullName()) . '</strong>,</p>
                    
                    <p>L\'événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> a été <strong>modifié</strong>.</p>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #f39c12; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #856404;">Nouvelles informations :</h3>
                        <p><strong>📅 Date et heure :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                        <p><strong>📝 Description :</strong> ' . htmlspecialchars($event->getDescription() ?? 'Aucune description') . '</p>
                    </div>
                    
                    <p>Merci de prendre note de ces modifications.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
    }

    /**
     * Template HTML pour les notifications de modification aux invités
     */
    private function renderUpdateTemplateForInvitation(Event $event, string $participantName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">🔔 Événement Modifié</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($participantName) . '</strong>,</p>
                    
                    <p>L\'événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> a été <strong>modifié</strong>.</p>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #f39c12; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #856404;">Nouvelles informations :</h3>
                        <p><strong>📅 Date et heure :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                        <p><strong>📝 Description :</strong> ' . htmlspecialchars($event->getDescription() ?? 'Aucune description') . '</p>
                    </div>
                    
                    <p>Merci de prendre note de ces modifications.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
    }

    /**
     * Template HTML pour les notifications d'annulation aux participants
     */
    private function renderCancelTemplate(Event $event, $user): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">❌ Événement Annulé</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($user->getFullName()) . '</strong>,</p>
                    
                    <p>Nous vous informons que l\'événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> prévu le <strong>' . $dateTime . '</strong> a été <strong>annulé</strong>.</p>
                    
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #721c24;">Détails de l\'événement annulé :</h3>
                        <p><strong>📅 Date prévue :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                    </div>
                    
                    <p>Merci de votre compréhension.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
    }

    /**
     * Template HTML pour les notifications d'annulation aux invités
     */
    private function renderCancelTemplateForInvitation(Event $event, string $participantName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">❌ Événement Annulé</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <p>Bonjour <strong>' . htmlspecialchars($participantName) . '</strong>,</p>
                    
                    <p>Nous vous informons que l\'événement <strong>' . htmlspecialchars($event->getTitle()) . '</strong> prévu le <strong>' . $dateTime . '</strong> a été <strong>annulé</strong>.</p>
                    
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545; margin: 20px 0;">
                        <h3 style="margin-top: 0; color: #721c24;">Détails de l\'événement annulé :</h3>
                        <p><strong>📅 Date prévue :</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Lieu :</strong> ' . htmlspecialchars($salleName) . '</p>
                    </div>
                    
                    <p>Merci de votre compréhension.</p>
                    <p>Cordialement,<br>L\'équipe EventHub</p>
                </div>
            </div>';
    }
}
