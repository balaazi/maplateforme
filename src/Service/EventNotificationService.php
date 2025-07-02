<?php
// src/Service/EventNotificationService.php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Repository\UserRepository;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EventNotificationService
{
private $mailer;
private $userRepository;

public function __construct(MailerInterface $mailer, UserRepository $userRepository)
{
$this->mailer = $mailer;
$this->userRepository = $userRepository;
}

// Cette méthode envoie une notification de mise à jour de l'événement
public function sendEventUpdateNotification(Event $event): void
{
        // Envoyer aux participants de l'événement
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user && $user->getEmail()) {
$email = (new Email())
                    ->from('nadiabalaazi@gmail.com')
->to($user->getEmail())
                    ->subject('🔔 Événement modifié : ' . $event->getTitle())
                    ->html($this->renderUpdateTemplate($event, $user));

                $this->mailer->send($email);
            }
        }

        // Envoyer aussi aux invités (invitations)
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getEmail()) {
                $participantName = $invitation->getParticipant() ? 
                    $invitation->getParticipant()->getFullName() : 'Invité(e)';

                $email = (new Email())
                    ->from('nadiaballaazi@gmail.com')
                    ->to($invitation->getEmail())
                    ->subject('🔔 Événement modifié : ' . $event->getTitle())
                    ->html($this->renderUpdateTemplateForInvitation($event, $participantName));

$this->mailer->send($email);
}
}
    }

public function sendEventCancelNotification(Event $event): void
{
        // Envoyer aux participants de l'événement
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user && $user->getEmail()) {
                $email = (new Email())
                    ->from('nadiabalaazi@gmail.com')
                    ->to($user->getEmail())
                    ->subject('❌ Événement annulé : ' . $event->getTitle())
                    ->html($this->renderCancelTemplate($event, $user));

                $this->mailer->send($email);
            }
        }

        // Envoyer aussi aux invités (invitations)
    foreach ($event->getInvitations() as $invit) {
        if ($invit->getEmail()) {
            $participant = $invit->getParticipant();
                $participantName = $participant ? $participant->getFullName() : 'Invité(e)';

            $email = (new Email())
                    ->from('nadiabalaazi@gmail.com')
                ->to($invit->getEmail())
                ->subject('❌ Événement annulé : ' . $event->getTitle())
                    ->html($this->renderCancelTemplateForInvitation($event, $participantName));

            $this->mailer->send($email);
        }
    }
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
