<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\Reminder;
use App\Repository\EventRepository;
use App\Repository\ReminderRepository;
use App\Service\MailerService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;

#[AsCommand(
    name: 'app:send-event-reminders-advanced',
    description: 'Système de rappels automatiques optimisé - vérifie chaque minute les événements dans 24h et 1h'
)]
class SendEventRemindersAdvancedCommand extends Command
{
    private const REMINDER_INTERVALS = [
        '24h' => 24 * 60, // 24 heures en minutes
        '1h' => 60        // 1 heure en minutes
    ];

    public function __construct(
        private EventRepository $eventRepository,
        private ReminderRepository $reminderRepository,
        private MailerService $mailerService,
        private NotificationService $notificationService,
        private EntityManagerInterface $em,
        private MailerInterface $mailer,
        private LoggerInterface $logger
    ) {
        parent::__construct();
    }
    
    protected function configure(): void
    {
        $this
            ->addOption(
                'reminder-type',
                't',
                InputOption::VALUE_REQUIRED,
                'Type de rappel à envoyer (24h, 1h, both)',
                'both'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Mode simulation - affiche les actions sans les exécuter'
            )
            ->addOption(
                'tolerance-minutes',
                null,
                InputOption::VALUE_REQUIRED,
                'Tolérance en minutes pour éviter les doublons',
                '2'
            )
            ->addOption(
                'cleanup',
                null,
                InputOption::VALUE_NONE,
                'Nettoie les anciens rappels déclenchés'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Système de Rappels Automatiques Optimisé');
        
        $reminderType = $input->getOption('reminder-type');
        $dryRun = $input->getOption('dry-run');
        $toleranceMinutes = (int) $input->getOption('tolerance-minutes');
        $cleanup = $input->getOption('cleanup');
        
        if ($dryRun) {
            $io->warning('⚠️ Mode simulation activé - Aucune action ne sera réellement exécutée');
        }
        
        // Validation du type de rappel
        if (!in_array($reminderType, ['24h', '1h', 'both'])) {
            $io->error('Type de rappel invalide. Utilisez: 24h, 1h, ou both');
            return Command::FAILURE;
        }

        $startTime = new \DateTime();
        $this->logger->info('Démarrage du processus de rappels', [
            'reminder_type' => $reminderType,
            'dry_run' => $dryRun,
            'tolerance_minutes' => $toleranceMinutes
        ]);

        try {
            // Nettoyage optionnel des anciens rappels
            if ($cleanup) {
                $this->cleanupOldReminders($io, $dryRun);
            }

            $stats = [
                'total_processed' => 0,
                'reminders_sent' => 0,
                'errors' => 0,
                'skipped' => 0
            ];

            // Traitement des rappels selon le type demandé
            if ($reminderType === 'both') {
                $stats24h = $this->processReminderType($io, '24h', $dryRun, $toleranceMinutes);
                $stats1h = $this->processReminderType($io, '1h', $dryRun, $toleranceMinutes);
                
                $stats['total_processed'] = $stats24h['total_processed'] + $stats1h['total_processed'];
                $stats['reminders_sent'] = $stats24h['reminders_sent'] + $stats1h['reminders_sent'];
                $stats['errors'] = $stats24h['errors'] + $stats1h['errors'];
                $stats['skipped'] = $stats24h['skipped'] + $stats1h['skipped'];
            } else {
                $stats = $this->processReminderType($io, $reminderType, $dryRun, $toleranceMinutes);
            }

            // Affichage du résumé
            $this->displaySummary($io, $stats, $startTime);
            
            $this->logger->info('Processus de rappels terminé', $stats);
            
            return Command::SUCCESS;

        } catch (\Exception $e) {
            $this->logger->error('Erreur lors du processus de rappels', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $io->error('Erreur critique: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function processReminderType(SymfonyStyle $io, string $type, bool $dryRun, int $toleranceMinutes): array
    {
        $io->section("📅 Traitement des rappels {$type}");
        
        $stats = [
            'total_processed' => 0,
            'reminders_sent' => 0,
            'errors' => 0,
            'skipped' => 0
        ];

        // Récupérer les événements qui nécessitent des rappels
        $events = $this->getEventsRequiringReminders($type, $toleranceMinutes);
        
        if (empty($events)) {
            $io->text("Aucun événement trouvé nécessitant des rappels {$type}");
            return $stats;
        }

        $io->text(sprintf('Traitement de %d événement(s) pour rappels %s', count($events), $type));
        $stats['total_processed'] = count($events);

        foreach ($events as $event) {
            try {
                $result = $this->processEventReminders($io, $event, $type, $dryRun);
                $stats['reminders_sent'] += $result['sent'];
                $stats['skipped'] += $result['skipped'];
                
            } catch (\Exception $e) {
                $stats['errors']++;
                $this->logger->error('Erreur lors du traitement de l\'événement', [
                    'event_id' => $event->getId(),
                    'event_title' => $event->getTitle(),
                    'error' => $e->getMessage()
                ]);
                $io->error(sprintf('Erreur événement "%s": %s', $event->getTitle(), $e->getMessage()));
            }
        }

        return $stats;
    }

    private function getEventsRequiringReminders(string $type, int $toleranceMinutes): array
    {
        $now = new \DateTime();
        $minutesBefore = self::REMINDER_INTERVALS[$type];
        
        // Calculer la fenêtre de temps pour les événements
        $targetTime = (clone $now)->modify("+{$minutesBefore} minutes");
        $startWindow = (clone $targetTime)->modify("-{$toleranceMinutes} minutes");
        $endWindow = (clone $targetTime)->modify("+{$toleranceMinutes} minutes");

        // Récupérer les événements dans cette fenêtre
        $events = $this->eventRepository->createQueryBuilder('e')
            ->where('e.dateHeure BETWEEN :start AND :end')
            ->andWhere('e.status != :cancelled OR e.status IS NULL')
            ->setParameter('start', $startWindow)
            ->setParameter('end', $endWindow)
            ->setParameter('cancelled', 'annulé')
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();

        // Filtrer les événements qui n'ont pas déjà reçu ce type de rappel
        return array_filter($events, function(Event $event) use ($type, $now) {
            return !$this->hasRecentReminder($event, $type, $now);
        });
    }

    private function hasRecentReminder(Event $event, string $type, \DateTime $now): bool
    {
        $minutesBefore = self::REMINDER_INTERVALS[$type];
        $expectedReminderTime = (clone $event->getDateHeure())->modify("-{$minutesBefore} minutes");
        
        // Vérifier s'il existe déjà un rappel récent pour cet événement et ce type
        $existingReminders = $this->reminderRepository->createQueryBuilder('r')
            ->where('r.event = :event')
            ->andWhere('r.type = :type')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.dueDate BETWEEN :start AND :end')
            ->setParameter('event', $event)
            ->setParameter('type', "event_reminder_{$type}")
            ->setParameter('triggered', true)
            ->setParameter('start', (clone $expectedReminderTime)->modify('-30 minutes'))
            ->setParameter('end', (clone $expectedReminderTime)->modify('+30 minutes'))
            ->getQuery()
            ->getResult();

        return !empty($existingReminders);
    }

    private function processEventReminders(SymfonyStyle $io, Event $event, string $type, bool $dryRun): array
    {
        $result = ['sent' => 0, 'skipped' => 0];
        
        if ($event->getStatus() === 'annulé') {
            $io->text(sprintf('   ⏭️  Événement "%s" annulé - ignoré', $event->getTitle()));
            $result['skipped']++;
            return $result;
        }

        $io->text(sprintf('   📅 Traitement: %s (%s)', 
            $event->getTitle(), 
            $event->getDateHeure()->format('d/m/Y H:i')
        ));

        // Envoyer des rappels aux invités
        foreach ($event->getInvitations() as $invitation) {
            if ($this->shouldSendReminderToInvitation($invitation)) {
                try {
                    if (!$dryRun) {
                        $this->sendReminderEmailToInvitee($invitation, $event, $type);
                        $this->createReminderRecord($invitation, $event, $type);
                    }
                    
                    $result['sent']++;
                    $io->text(sprintf('      ✅ Rappel %s %senvoyé à: %s (%s)', 
                        $type,
                        $dryRun ? '(simulation) ' : '',
                        $invitation->getName(),
                        $invitation->getStatus()
                    ));
                    
                } catch (\Exception $e) {
                    $this->logger->error('Erreur envoi rappel', [
                        'invitation_id' => $invitation->getId(),
                        'event_id' => $event->getId(),
                        'error' => $e->getMessage()
                    ]);
                    $io->error(sprintf('      ❌ Erreur envoi à %s: %s', 
                        $invitation->getName(), 
                        $e->getMessage()
                    ));
                }
            } else {
                $result['skipped']++;
                $io->text(sprintf('      ⏭️  Rappel ignoré pour: %s (statut: %s)', 
                    $invitation->getName(),
                    $invitation->getStatus()
                ));
            }
        }

        return $result;
    }

    private function shouldSendReminderToInvitation($invitation): bool
    {
        // Envoyer des rappels à toutes les invitations valides sauf expirées
        return $invitation->getEmail() && 
               $invitation->getStatus() !== 'expired' &&
               filter_var($invitation->getEmail(), FILTER_VALIDATE_EMAIL);
    }

    private function createReminderRecord($invitation, Event $event, string $type): void
    {
        $reminder = new Reminder();
        $reminder->setTitle("Rappel {$type} - " . $event->getTitle());
        $reminder->setDescription("Rappel automatique envoyé à " . $invitation->getName());
        $reminder->setDueDate(new \DateTime());
        $reminder->setType("event_reminder_{$type}");
        $reminder->setIsTriggered(true);
        $reminder->setTriggeredAt(new \DateTime());
        $reminder->setEvent($event);
        
        // Associer à l'utilisateur si l'invitation a un utilisateur lié
        if ($invitation->getUser()) {
            $reminder->setUser($invitation->getUser());
        }

        $this->em->persist($reminder);
        $this->em->flush();
    }

    private function sendReminderEmailToInvitee($invitation, Event $event, string $type): void
    {
        $hoursBefore = $type === '24h' ? 24 : 1;
        $subject = $type === '24h' ? '⏰ Rappel 24h - ' : '🚨 Rappel 1h - ';
        $subject .= $event->getTitle();
        
        // Créer un objet utilisateur temporaire pour l'email
        $tempUser = new \stdClass();
        $tempUser->email = $invitation->getEmail();
        $tempUser->fullName = $invitation->getName();
        $tempUser->prenom = explode(' ', $invitation->getName())[0];
        
        $email = (new TemplatedEmail())
            ->from('nadiabalaazi@gmail.com')
            ->to($invitation->getEmail())
            ->subject($subject)
            ->htmlTemplate('emails/reminder_advanced.html.twig')
            ->context([
                'event' => $event,
                'user' => $tempUser,
                'invitation' => $invitation,
                'reminder_type' => $type,
                'hours_before' => $hoursBefore,
                'event_date' => $event->getDateHeure(),
                'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
                'event_duration' => $event->getDuree(),
                'event_description' => $event->getDescription(),
                'current_time' => new \DateTime()
            ]);

        $this->mailer->send($email);
        
        $this->logger->info('Rappel envoyé', [
            'event_id' => $event->getId(),
            'invitation_email' => $invitation->getEmail(),
            'reminder_type' => $type
        ]);
    }

    private function cleanupOldReminders(SymfonyStyle $io, bool $dryRun): void
    {
        $io->section('🧹 Nettoyage des anciens rappels');
        
        $cutoffDate = (new \DateTime())->modify('-7 days');
        
        $qb = $this->em->createQueryBuilder()
            ->select('COUNT(r.id)')
            ->from(Reminder::class, 'r')
            ->where('r.isTriggered = :triggered')
            ->andWhere('r.triggeredAt < :cutoff')
            ->setParameter('triggered', true)
            ->setParameter('cutoff', $cutoffDate);
            
        $count = $qb->getQuery()->getSingleScalarResult();
        
        if ($count > 0) {
            if (!$dryRun) {
                $this->em->createQueryBuilder()
                    ->delete(Reminder::class, 'r')
                    ->where('r.isTriggered = :triggered')
                    ->andWhere('r.triggeredAt < :cutoff')
                    ->setParameter('triggered', true)
                    ->setParameter('cutoff', $cutoffDate)
                    ->getQuery()
                    ->execute();
            }
            
            $io->text(sprintf('%s%d ancien(s) rappel(s) supprimé(s)', 
                $dryRun ? '(simulation) ' : '', 
                $count
            ));
        } else {
            $io->text('Aucun ancien rappel à nettoyer');
        }
    }

    private function displaySummary(SymfonyStyle $io, array $stats, \DateTime $startTime): void
    {
        $duration = (new \DateTime())->getTimestamp() - $startTime->getTimestamp();
        
        $io->section('📊 Résumé de l\'exécution');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Événements traités', $stats['total_processed']],
                ['Rappels envoyés', $stats['reminders_sent']],
                ['Rappels ignorés', $stats['skipped']],
                ['Erreurs', $stats['errors']],
                ['Durée d\'exécution', $duration . ' secondes'],
                ['Timestamp', (new \DateTime())->format('Y-m-d H:i:s')]
            ]
        );

        if ($stats['reminders_sent'] > 0) {
            $io->success(sprintf('✅ Processus terminé: %d rappel(s) envoyé(s)', $stats['reminders_sent']));
        } elseif ($stats['total_processed'] === 0) {
            $io->info('ℹ️  Aucun événement nécessitant des rappels trouvé');
        } else {
            $io->warning('⚠️  Aucun rappel envoyé malgré des événements traités');
        }
    }
}
