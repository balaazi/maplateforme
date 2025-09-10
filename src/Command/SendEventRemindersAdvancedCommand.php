<?php

namespace App\Command;

use App\Entity\Reminder;
use App\Repository\EventRepository;
use App\Repository\ReminderRepository;
use App\Service\MailerService;
use App\Service\NotificationService;
use Doctrine\ORM\EntityManagerInterface;
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
    description: 'Envoie des rappels automatiques 24h et 1h avant les événements UNIQUEMENT aux personnes invitées par email et notification.'
)]
class SendEventRemindersAdvancedCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private ReminderRepository $reminderRepository,
        private MailerService $mailerService,
        private NotificationService $notificationService,
        private EntityManagerInterface $em,
        private MailerInterface $mailer
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
                'force-date',
                'f',
                InputOption::VALUE_REQUIRED,
                'Force l\'envoi des rappels pour une date spécifique (format: Y-m-d)'
            )
            ->addOption(
                'test-mode',
                null,
                InputOption::VALUE_NONE,
                'Mode test - n\'envoie pas réellement les emails'
            )
            ->addOption(
                'dry-run',
                null,
                InputOption::VALUE_NONE,
                'Affiche seulement les rappels qui seraient envoyés sans les envoyer'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Système de Rappels Avancés - 24h et 1h avant événements');
        
        $reminderType = $input->getOption('reminder-type');
        $testMode = $input->getOption('test-mode');
        $dryRun = $input->getOption('dry-run');
        $forceDate = $input->getOption('force-date');
        
        if ($testMode || $dryRun) {
            $io->warning('⚠️ Mode ' . ($testMode ? 'test' : 'dry-run') . ' activé - Les emails ne seront pas réellement envoyés');
        }
        
        // Validation du type de rappel
        if (!in_array($reminderType, ['24h', '1h', 'both'])) {
            $io->error('Type de rappel invalide. Utilisez: 24h, 1h, ou both');
            return Command::FAILURE;
        }
        
        $totalReminders = 0;
        $stats = [
            '24h' => ['events' => 0, 'reminders' => 0, 'errors' => 0],
            '1h' => ['events' => 0, 'reminders' => 0, 'errors' => 0]
        ];
        
        // Traitement des rappels 24h
        if ($reminderType === '24h' || $reminderType === 'both') {
            $io->section('📅 Traitement des rappels 24h avant');
            $result24h = $this->processReminders($io, '24h', $forceDate, $testMode, $dryRun);
            $stats['24h'] = $result24h;
            $totalReminders += $result24h['reminders'];
        }
        
        // Traitement des rappels 1h
        if ($reminderType === '1h' || $reminderType === 'both') {
            $io->section('⏰ Traitement des rappels 1h avant');
            $result1h = $this->processReminders($io, '1h', $forceDate, $testMode, $dryRun);
            $stats['1h'] = $result1h;
            $totalReminders += $result1h['reminders'];
        }
        
        // Affichage du résumé
        $io->section('📊 Résumé des rappels');
        $io->table(
            ['Type', 'Événements', 'Rappels envoyés', 'Erreurs'],
            [
                ['24h avant', $stats['24h']['events'], $stats['24h']['reminders'], $stats['24h']['errors']],
                ['1h avant', $stats['1h']['events'], $stats['1h']['reminders'], $stats['1h']['errors']],
                ['TOTAL', $stats['24h']['events'] + $stats['1h']['events'], $totalReminders, $stats['24h']['errors'] + $stats['1h']['errors']]
            ]
        );
        
        if ($totalReminders > 0) {
            $io->success(sprintf('✅ Processus terminé: %d rappel(s) envoyé(s) au total', $totalReminders));
        } else {
            $io->info('ℹ️  Aucun rappel à envoyer pour cette période');
        }

        return Command::SUCCESS;
    }
    
    private function processReminders(SymfonyStyle $io, string $type, ?string $forceDate, bool $testMode, bool $dryRun): array
    {
        $hoursBefore = $type === '24h' ? 24 : 1;
         $events = $this->getEventsForReminder($type, $forceDate);
        $reminders = $this->getEventsForReminder($type, $forceDate);

        if (empty($events)) {
            $io->text(sprintf('Aucun événement trouvé pour les rappels %s', $type));
            return ['events' => 0, 'reminders' => 0, 'errors' => 0];
        }
        
        $io->text(sprintf('Traitement de %d événement(s) pour rappels %s', count($events), $type));
        
        $reminder = 0;
        $errors = 0;
        $usersNotified = [];
        
        foreach ($reminders as $rem) {
            /**
             * @var Reminder $rem
             */
            if ($rem->isTriggered()){
                $io->text(sprintf("Reminder igonre"));
                continue;
            }
            if($rem->getEvent()->getDateHeure() == $rem->getDueDate()){
                $io->text(sprintf('   📅 Traitement: %s', $rem->getEvent()->getTitle()));

                foreach ($rem->getEvent()->getInvitations() as $invitation) {
                    $io->text(sprintf('      ✅ Rappel %s %senvoyé à l\'invité: %s (%s)',
                        $type,
                        ($testMode || $dryRun) ? '(simulation) ' : '',
                        $invitation->getName(),
                        $invitation->getStatus()
                    ));
                    $this->sendReminderEmailToInvitee($invitation, $rem->getEvent(), $type);
                }

            }
        }
        /*foreach ($events as $event) {
            if ($event->getStatus() === 'annulé') {
                $io->text(sprintf('   ⏭️  Événement "%s" annulé - ignoré', $event->getTitle()));
                continue;
            }
            
            $io->text(sprintf('   📅 Traitement: %s', $event->getTitle()));
            $eventReminders = 0;
            
            // Rappels UNIQUEMENT aux invités (personnes à qui une invitation a été envoyée)
            foreach ($event->getInvitations() as $invitation) {
                // Envoyer des rappels à TOUTES les invitations envoyées, peu importe le statut
                // (pending, accepted, declined) - sauf expired
                if ($invitation->getStatus() !== 'expired' && $invitation->getEmail()) {
                    $uniqueKey = $invitation->getId() . '_' . $event->getId() . '_' . $type;
                    
                    if (!in_array($uniqueKey, $usersNotified)) {
                        try {
                            if (!$testMode && !$dryRun) {
                                $this->sendReminderEmailToInvitee($invitation, $event, $type);
                            }
                            $usersNotified[] = $uniqueKey;
                            $eventReminders++;
                            $io->text(sprintf('      ✅ Rappel %s %senvoyé à l\'invité: %s (%s)', 
                                $type,
                                ($testMode || $dryRun) ? '(simulation) ' : '',
                                $invitation->getName(),
                                $invitation->getStatus()
                            ));
                        } catch (\Exception $e) {
                            $errors++;
                            $io->error(sprintf('      ❌ Erreur envoi rappel invité %s: %s', $invitation->getName(), $e->getMessage()));
                        }
                    }
                }
            }
            
            $reminder += $eventReminders;
            $io->text(sprintf('      📊 %d rappel(s) envoyé(s) pour cet événement', $eventReminders));
        }
        */
        return ['events' => count($events), 'reminders' => $reminder, 'errors' => $errors];
    }
    
    private function getEventsForReminder(string $type, ?string $forceDate): array
    {
        $hoursBefore = $type === '24h' ? 24 : 1;
        
        if ($forceDate) {
            try {
                $targetDate = new \DateTime($forceDate);
                $targetDate->setTime(0, 0, 0);
            } catch (\Exception $e) {
                throw new \InvalidArgumentException('Format de date invalide: ' . $forceDate);
            }
        } else {
            $targetDate = new \DateTime();
        }
        
        // Calculer la plage de temps pour les événements
        $startTime = (clone $targetDate)->modify("+{$hoursBefore} hours")->setTime(0, 0, 0);
        $endTime = (clone $startTime)->modify('+1 day');
        return $this->reminderRepository->findUpcomingReminders(5);

       // return $this->reminderRepository->findByDateRange($startTime, $endTime);
    }
    
    private function sendReminderEmail($user, $event, string $type): void
    {
        $hoursBefore = $type === '24h' ? 24 : 1;
        $subject = $type === '24h' ? '⏰ Rappel 24h - ' : '🚨 Rappel 1h - ';
        $subject .= $event->getTitle();
        
        $email = (new TemplatedEmail())
            ->from('nadiabalaazi@gmail.com')
            ->to($user->getEmail())
            ->subject($subject)
            ->htmlTemplate('emails/reminder_advanced.html.twig')
            ->context([
                'event' => $event,
                'user' => $user,
                'reminder_type' => $type,
                'hours_before' => $hoursBefore,
                'event_date' => $event->getDateHeure(),
                'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
                'event_duration' => $event->getDuree(),
                'event_description' => $event->getDescription()
            ]);

        $this->mailer->send($email);
    }
    
    private function sendReminderEmailToInvitee($invitation, $event, string $type): void
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
                'reminder_type' => $type,
                'hours_before' => $hoursBefore,
                'event_date' => $event->getDateHeure(),
                'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
                'event_duration' => $event->getDuree(),
                'event_description' => $event->getDescription()
            ]);

        $this->mailer->send($email);
    }
}
