<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\User;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class AdminNotificationService
{
    private const ADMIN_EMAIL = 'enterpriseeventhub@gmail.com';
    
    private MailerInterface $mailer;

    public function __construct(MailerInterface $mailer)
    {
        $this->mailer = $mailer;
    }

    /**
     * Notifie l'administrateur de la création d'un événement
     */
    public function notifyEventCreated(Event $event): void
    {
        $organizer = $event->getOrganizer();
        $organizerName = $organizer ? $organizer->getFullName() : 'Utilisateur inconnu';
        
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to(self::ADMIN_EMAIL)
            ->subject('🎉 Nouvel événement créé - ' . $event->getTitle())
            ->html($this->renderEventCreatedTemplate($event, $organizerName));

        $this->mailer->send($email);
    }

    /**
     * Notifie l'administrateur de la modification d'un événement
     */
    public function notifyEventUpdated(Event $event): void
    {
        $organizer = $event->getOrganizer();
        $organizerName = $organizer ? $organizer->getFullName() : 'Utilisateur inconnu';
        
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to(self::ADMIN_EMAIL)
            ->subject('✏️ Événement modifié - ' . $event->getTitle())
            ->html($this->renderEventUpdatedTemplate($event, $organizerName));

        $this->mailer->send($email);
    }

    /**
     * Notifie l'administrateur de l'annulation d'un événement
     */
    public function notifyEventCancelled(Event $event): void
    {
        $organizer = $event->getOrganizer();
        $organizerName = $organizer ? $organizer->getFullName() : 'Utilisateur inconnu';
        
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to(self::ADMIN_EMAIL)
            ->subject('❌ Événement annulé - ' . $event->getTitle())
            ->html($this->renderEventCancelledTemplate($event, $organizerName));

        $this->mailer->send($email);
    }

    /**
     * Notifie l'administrateur de la suppression d'un événement
     */
    public function notifyEventDeleted(Event $event): void
    {
        $organizer = $event->getOrganizer();
        $organizerName = $organizer ? $organizer->getFullName() : 'Utilisateur inconnu';
        
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to(self::ADMIN_EMAIL)
            ->subject('🗑️ Événement supprimé - ' . $event->getTitle())
            ->html($this->renderEventDeletedTemplate($event, $organizerName));

        $this->mailer->send($email);
    }

    private function renderEventCreatedTemplate(Event $event, string $organizerName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        $title = htmlspecialchars($event->getTitle());
        $description = htmlspecialchars($event->getDescription() ?? 'Aucune description');
        $organizerEscaped = htmlspecialchars($organizerName);
        $salleEscaped = htmlspecialchars($salleName);
        $duree = $event->getDuree() ?? 0;
        $category = htmlspecialchars($event->getCategory() ?? 'Non définie');
        $timestamp = date('d/m/Y H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">🎉 Nouvel Événement Créé</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #333; margin-top: 0;">' . $title . '</h2>
                    
                    <div style="margin: 20px 0;">
                        <p><strong>📝 Description:</strong> ' . $description . '</p>
                        <p><strong>👤 Organisateur:</strong> ' . $organizerEscaped . '</p>
                        <p><strong>📅 Date et heure:</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Salle:</strong> ' . $salleEscaped . '</p>
                        <p><strong>⏱️ Durée:</strong> ' . $duree . ' minutes</p>
                        <p><strong>🏷️ Catégorie:</strong> ' . $category . '</p>
                    </div>
                    
                    <div style="background-color: #e8f5e8; padding: 15px; border-radius: 5px; border-left: 4px solid #28a745;">
                        <p style="margin: 0; color: #155724;"><strong>Action:</strong> Création d\'un nouvel événement</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    <p>Notification automatique EventHub - ' . $timestamp . '</p>
                </div>
            </div>';
    }

    private function renderEventUpdatedTemplate(Event $event, string $organizerName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        $title = htmlspecialchars($event->getTitle());
        $description = htmlspecialchars($event->getDescription() ?? 'Aucune description');
        $organizerEscaped = htmlspecialchars($organizerName);
        $salleEscaped = htmlspecialchars($salleName);
        $duree = $event->getDuree() ?? 0;
        $category = htmlspecialchars($event->getCategory() ?? 'Non définie');
        $timestamp = date('d/m/Y H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #f39c12 0%, #f1c40f 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">✏️ Événement Modifié</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #333; margin-top: 0;">' . $title . '</h2>
                    
                    <div style="margin: 20px 0;">
                        <p><strong>📝 Description:</strong> ' . $description . '</p>
                        <p><strong>👤 Organisateur:</strong> ' . $organizerEscaped . '</p>
                        <p><strong>📅 Date et heure:</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Salle:</strong> ' . $salleEscaped . '</p>
                        <p><strong>⏱️ Durée:</strong> ' . $duree . ' minutes</p>
                        <p><strong>🏷️ Catégorie:</strong> ' . $category . '</p>
                    </div>
                    
                    <div style="background-color: #fff3cd; padding: 15px; border-radius: 5px; border-left: 4px solid #f39c12;">
                        <p style="margin: 0; color: #856404;"><strong>Action:</strong> Modification des informations de l\'événement</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    <p>Notification automatique EventHub - ' . $timestamp . '</p>
                </div>
            </div>';
    }

    private function renderEventCancelledTemplate(Event $event, string $organizerName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        $title = htmlspecialchars($event->getTitle());
        $description = htmlspecialchars($event->getDescription() ?? 'Aucune description');
        $organizerEscaped = htmlspecialchars($organizerName);
        $salleEscaped = htmlspecialchars($salleName);
        $duree = $event->getDuree() ?? 0;
        $category = htmlspecialchars($event->getCategory() ?? 'Non définie');
        $timestamp = date('d/m/Y H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #e74c3c 0%, #c0392b 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">❌ Événement Annulé</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #333; margin-top: 0;">' . $title . '</h2>
                    
                    <div style="margin: 20px 0;">
                        <p><strong>📝 Description:</strong> ' . $description . '</p>
                        <p><strong>👤 Organisateur:</strong> ' . $organizerEscaped . '</p>
                        <p><strong>📅 Date et heure (initialement prévue):</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Salle:</strong> ' . $salleEscaped . '</p>
                        <p><strong>⏱️ Durée:</strong> ' . $duree . ' minutes</p>
                        <p><strong>🏷️ Catégorie:</strong> ' . $category . '</p>
                    </div>
                    
                    <div style="background-color: #f8d7da; padding: 15px; border-radius: 5px; border-left: 4px solid #dc3545;">
                        <p style="margin: 0; color: #721c24;"><strong>Action:</strong> Annulation de l\'événement</p>
                        <p style="margin: 5px 0 0 0; color: #721c24;">Les participants ont été automatiquement notifiés.</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    <p>Notification automatique EventHub - ' . $timestamp . '</p>
                </div>
            </div>';
    }

    private function renderEventDeletedTemplate(Event $event, string $organizerName): string
    {
        $salleName = $event->getSalle() ? $event->getSalle()->getNom() : 'Non définie';
        $dateTime = $event->getDateHeure()->format('d/m/Y à H:i');
        $title = htmlspecialchars($event->getTitle());
        $description = htmlspecialchars($event->getDescription() ?? 'Aucune description');
        $organizerEscaped = htmlspecialchars($organizerName);
        $salleEscaped = htmlspecialchars($salleName);
        $duree = $event->getDuree() ?? 0;
        $category = htmlspecialchars($event->getCategory() ?? 'Non définie');
        $timestamp = date('d/m/Y H:i');
        
        return '<div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px; background-color: #f8f9fa;">
                <div style="background: linear-gradient(135deg, #6c757d 0%, #495057 100%); padding: 30px; border-radius: 10px; text-align: center; margin-bottom: 20px;">
                    <h1 style="color: white; margin: 0; font-size: 24px;">🗑️ Événement Supprimé</h1>
                </div>
                
                <div style="background: white; padding: 25px; border-radius: 10px; box-shadow: 0 2px 10px rgba(0,0,0,0.1);">
                    <h2 style="color: #333; margin-top: 0;">' . $title . '</h2>
                    
                    <div style="margin: 20px 0;">
                        <p><strong>📝 Description:</strong> ' . $description . '</p>
                        <p><strong>👤 Organisateur:</strong> ' . $organizerEscaped . '</p>
                        <p><strong>📅 Date et heure:</strong> ' . $dateTime . '</p>
                        <p><strong>🏢 Salle:</strong> ' . $salleEscaped . '</p>
                        <p><strong>⏱️ Durée:</strong> ' . $duree . ' minutes</p>
                        <p><strong>🏷️ Catégorie:</strong> ' . $category . '</p>
                    </div>
                    
                    <div style="background-color: #d1ecf1; padding: 15px; border-radius: 5px; border-left: 4px solid #6c757d;">
                        <p style="margin: 0; color: #0c5460;"><strong>Action:</strong> Suppression définitive de l\'événement</p>
                    </div>
                </div>
                
                <div style="text-align: center; margin-top: 20px; font-size: 12px; color: #666;">
                    <p>Notification automatique EventHub - ' . $timestamp . '</p>
                </div>
            </div>';
    }
} 