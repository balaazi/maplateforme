<?php

namespace App\Service;

use App\Entity\Event;
use App\Entity\Invitation;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

class InvitationReminderService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private EventRepository $eventRepository,
        private InvitationRepository $invitationRepository,
        private MailerService $mailerService,
        private NotificationService $notificationService,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
    }

    /**
     * Envoie des rappels pour tous les événements dans la plage spécifiée
     * UNIQUEMENT aux personnes à qui une invitation a été envoyée
     */
    public function sendRemindersForTimeRange(string $reminderType, ?\DateTime $forceDate = null): array
    {
        $hoursBefore = $reminderType === '24h' ? 24 : 1;
        $events = $this->getEventsForReminder($reminderType, $forceDate);
        
        $results = [
            'events_processed' => 0,
            'reminders_sent' => 0,
            'errors' => 0,
            'details' => []
        ];
        
        foreach ($events as $event) {
            if ($event->getStatus() === 'annulé') {
                continue;
            }
            
            $results['events_processed']++;
            $eventResult = $this->sendRemindersForEvent($event, $reminderType);
            
            $results['reminders_sent'] += $eventResult['reminders_sent'];
            $results['errors'] += $eventResult['errors'];
            $results['details'][] = [
                'event' => $event->getTitle(),
                'reminders_sent' => $eventResult['reminders_sent'],
                'errors' => $eventResult['errors']
            ];
        }
        
        $this->logger->info('Rappels d\'invitations envoyés', [
            'type' => $reminderType,
            'events_processed' => $results['events_processed'],
            'reminders_sent' => $results['reminders_sent'],
            'errors' => $results['errors']
        ]);
        
        return $results;
    }
    
    /**
     * Envoie des rappels pour un événement spécifique
     * UNIQUEMENT aux personnes à qui une invitation a été envoyée
     */
    public function sendRemindersForEvent(Event $event, string $reminderType): array
    {
        $results = [
            'reminders_sent' => 0,
            'errors' => 0
        ];
        
        $usersNotified = [];
        
        // Rappels UNIQUEMENT aux invités (personnes à qui une invitation a été envoyée)
        foreach ($event->getInvitations() as $invitation) {
            // Envoyer des rappels à TOUTES les invitations envoyées, peu importe le statut
            // (pending, accepted, declined) - sauf expired
            if ($invitation->getStatus() !== 'expired' && $invitation->getEmail()) {
                $uniqueKey = $invitation->getId() . '_' . $event->getId() . '_' . $reminderType;
                
                if (!in_array($uniqueKey, $usersNotified)) {
                    try {
                        $this->sendReminderToInvitee($invitation, $event, $reminderType);
                        $usersNotified[] = $uniqueKey;
                        $results['reminders_sent']++;
                        
                        $this->logger->info('Rappel envoyé à l\'invité', [
                            'invitation' => $invitation->getName(),
                            'email' => $invitation->getEmail(),
                            'status' => $invitation->getStatus(),
                            'event' => $event->getTitle(),
                            'type' => $reminderType
                        ]);
                    } catch (\Exception $e) {
                        $results['errors']++;
                        $this->logger->error('Erreur envoi rappel invité', [
                            'invitation' => $invitation->getName(),
                            'email' => $invitation->getEmail(),
                            'event' => $event->getTitle(),
                            'type' => $reminderType,
                            'error' => $e->getMessage()
                        ]);
                    }
                }
            }
        }
        
        return $results;
    }
    
    /**
     * Envoie un rappel à un invité (sans compte utilisateur)
     */
    private function sendReminderToInvitee(Invitation $invitation, Event $event, string $reminderType): void
    {
        $hoursBefore = $reminderType === '24h' ? 24 : 1;
        $subject = $reminderType === '24h' ? '⏰ Rappel 24h - ' : '🚨 Rappel 1h - ';
        $subject .= $event->getTitle();
        
        // Créer un objet utilisateur temporaire pour l'email
        $tempUser = new \stdClass();
        $tempUser->email = $invitation->getEmail();
        $tempUser->fullName = $invitation->getName();
        $tempUser->prenom = explode(' ', $invitation->getName())[0];
        
        $email = (new TemplatedEmail())
            ->from('EventHub <nadiabalaazi@gmail.com>')
            ->to($invitation->getEmail())
            ->subject($subject)
            ->htmlTemplate('emails/invitation_reminder.html.twig')
            ->context([
                'event' => $event,
                'invitation' => $invitation,
                'user' => $tempUser,
                'reminder_type' => $reminderType,
                'hours_before' => $hoursBefore,
                'event_date' => $event->getDateHeure(),
                'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
                'event_duration' => $event->getDuree(),
                'event_description' => $event->getDescription()
            ]);

        $this->mailer->send($email);
    }
    
    /**
     * Récupère les événements pour lesquels envoyer des rappels
     */
    private function getEventsForReminder(string $reminderType, ?\DateTime $forceDate = null): array
    {
        $hoursBefore = $reminderType === '24h' ? 24 : 1;
        
        if ($forceDate) {
            $targetDate = clone $forceDate;
        } else {
            $targetDate = new \DateTime();
        }
        
        // Calculer la plage de temps pour les événements
        $startTime = (clone $targetDate)->modify("+{$hoursBefore} hours")->setTime(0, 0, 0);
        $endTime = (clone $startTime)->modify('+1 day');
        
        return $this->eventRepository->findByDateRange($startTime, $endTime);
    }
    
    /**
     * Crée des rappels programmés pour les événements à venir
     */
    public function createScheduledReminders(int $daysAhead = 7): array
    {
        $startDate = new \DateTime();
        $endDate = (new \DateTime())->modify("+{$daysAhead} days");
        
        $events = $this->eventRepository->findByDateRange($startDate, $endDate);
        $createdReminders = [];
        
        foreach ($events as $event) {
            if ($event->getStatus() === 'annulé') {
                continue;
            }
            
            // Créer rappel 24h avant
            $reminder24h = $this->createScheduledReminder($event, '24h');
            if ($reminder24h) {
                $createdReminders[] = $reminder24h;
            }
            
            // Créer rappel 1h avant
            $reminder1h = $this->createScheduledReminder($event, '1h');
            if ($reminder1h) {
                $createdReminders[] = $reminder1h;
            }
        }
        
        return $createdReminders;
    }
    
    /**
     * Crée un rappel programmé pour un événement
     */
    private function createScheduledReminder(Event $event, string $reminderType): ?array
    {
        $hoursBefore = $reminderType === '24h' ? 24 : 1;
        $eventDate = $event->getDateHeure();
        
        if (!$eventDate) {
            return null;
        }
        
        $dueDate = (clone $eventDate)->modify("-{$hoursBefore} hours");
        
        // Si la date d'échéance est déjà passée, ne pas créer de rappel
        if ($dueDate <= new \DateTime()) {
            return null;
        }
        
        return [
            'event' => $event->getTitle(),
            'type' => $reminderType,
            'due_date' => $dueDate,
            'event_date' => $eventDate,
            'hours_before' => $hoursBefore
        ];
    }
    
    /**
     * Vérifie et envoie les rappels programmés
     */
    public function processScheduledReminders(): array
    {
        $now = new \DateTime();
        $results = [
            '24h' => $this->sendRemindersForTimeRange('24h'),
            '1h' => $this->sendRemindersForTimeRange('1h')
        ];
        
        return $results;
    }
    
    /**
     * Envoie un rappel personnalisé à une invitation spécifique
     */
    public function sendCustomReminder(Invitation $invitation, string $reminderType, ?string $customMessage = null): bool
    {
        try {
            $event = $invitation->getEvent();
            if (!$event) {
                throw new \InvalidArgumentException('L\'invitation n\'est pas associée à un événement');
            }
            
            $this->sendReminderToInvitee($invitation, $event, $reminderType);
            
            $this->logger->info('Rappel personnalisé envoyé', [
                'invitation_id' => $invitation->getId(),
                'email' => $invitation->getEmail(),
                'event' => $event->getTitle(),
                'type' => $reminderType
            ]);
            
            return true;
        } catch (\Exception $e) {
            $this->logger->error('Erreur envoi rappel personnalisé', [
                'invitation_id' => $invitation->getId(),
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }
    
    /**
     * Récupère les statistiques des rappels d'invitations
     */
    public function getReminderStats(): array
    {
        $totalInvitations = $this->invitationRepository->count([]);
        $pendingInvitations = $this->invitationRepository->count(['status' => 'pending']);
        $acceptedInvitations = $this->invitationRepository->count(['status' => 'accepted']);
        $declinedInvitations = $this->invitationRepository->count(['status' => 'declined']);
        $expiredInvitations = $this->invitationRepository->count(['status' => 'expired']);
        
        return [
            'total_invitations' => $totalInvitations,
            'pending' => $pendingInvitations,
            'accepted' => $acceptedInvitations,
            'declined' => $declinedInvitations,
            'expired' => $expiredInvitations
        ];
    }
}
