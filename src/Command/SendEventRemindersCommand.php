<?php

namespace App\Command;

use App\Repository\EventRepository;
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
use Symfony\Component\Mime\Email;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie des rappels automatiques la veille des événements.'
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
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
                'force-date',
                null,
                InputOption::VALUE_REQUIRED,
                'Force l\'envoi des rappels pour une date spécifique (format: Y-m-d)'
            )
            ->addOption(
                'test-mode',
                null,
                InputOption::VALUE_NONE,
                'Mode test - n\'envoie pas réellement les emails'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('🔔 Envoi des rappels d\'événements');
        
        $testMode = $input->getOption('test-mode');
        if ($testMode) {
            $io->warning('⚠️ Mode test activé - Les emails ne seront pas réellement envoyés');
        }
        
        // Vérifier si une date spécifique est demandée
        $forceDate = $input->getOption('force-date');
        
        if ($forceDate) {
            try {
                $tomorrow = new \DateTime($forceDate);
                $tomorrow->setTime(0, 0, 0);
                $afterTomorrow = clone $tomorrow;
                $afterTomorrow->modify('+1 day');
                $io->note(sprintf('Mode forcé pour la date: %s', $forceDate));
            } catch (\Exception $e) {
                $io->error(sprintf('Format de date invalide: %s. Utilisez le format Y-m-d (ex: 2025-09-07)', $forceDate));
                return Command::FAILURE;
            }
        } else {
            // Récupère les événements de demain (comportement par défaut)
            $tomorrow = (new \DateTime())->modify('+1 day')->setTime(0, 0, 0);
            $afterTomorrow = (new \DateTime())->modify('+2 day')->setTime(0, 0, 0);
        }

        $io->note(sprintf('Recherche d\'événements entre %s et %s', 
            $tomorrow->format('d/m/Y H:i'), 
            $afterTomorrow->format('d/m/Y H:i')
        ));

        $events = $this->eventRepository->findByDateRange($tomorrow, $afterTomorrow);

        if (empty($events)) {
            $io->success('Aucun événement trouvé pour demain. Aucun rappel à envoyer.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d événement(s)', count($events)));

        $totalReminders = 0;
        $usersNotified = [];

        foreach ($events as $event) {
            // Ignorer les événements annulés
            if ($event->getStatus() === 'annulé') {
                $io->note(sprintf('Événement "%s" annulé - ignoré', $event->getTitle()));
                continue;
            }

            $io->text(sprintf('📅 Traitement de l\'événement: %s', $event->getTitle()));
            $eventReminders = 0;

            // 1. Rappel à l'organisateur
            $organizer = $event->getOrganizer();
            if ($organizer && $organizer->isNotifyByEmail() && $organizer->getEmail()) {
                $uniqueKey = $organizer->getId() . '_' . $event->getId();
                
                if (!in_array($uniqueKey, $usersNotified)) {
                    try {
                        if (!$testMode) {
                            $this->mailerService->sendReminderEmail($organizer, $event);
                            $this->notificationService->createEventReminderNotification($organizer, $event);
                        }
                        $usersNotified[] = $uniqueKey;
                        $eventReminders++;
                        $io->text(sprintf('   ✅ Rappel %senvoyé à l\'organisateur: %s', 
                            $testMode ? '(simulation) ' : '',
                            $organizer->getFullName()
                        ));
                    } catch (\Exception $e) {
                        $io->error(sprintf('   ❌ Erreur envoi rappel organisateur %s: %s', $organizer->getFullName(), $e->getMessage()));
                    }
                }
            }

            // 2. Rappels aux participants
            foreach ($event->getParticipations() as $participation) {
                $user = $participation->getUser();
                
                if ($user && $user->isNotifyByEmail() && $user->getEmail()) {
                    $uniqueKey = $user->getId() . '_' . $event->getId();
                    
                    if (!in_array($uniqueKey, $usersNotified)) {
                        try {
                            if (!$testMode) {
                                $this->mailerService->sendReminderEmail($user, $event);
                                $this->notificationService->createEventReminderNotification($user, $event);
                            }
                            $usersNotified[] = $uniqueKey;
                            $eventReminders++;
                            $io->text(sprintf('   ✅ Rappel %senvoyé au participant: %s', 
                                $testMode ? '(simulation) ' : '', 
                                $user->getFullName()
                            ));
                        } catch (\Exception $e) {
                            $io->error(sprintf('   ❌ Erreur envoi rappel participant %s: %s', $user->getFullName(), $e->getMessage()));
                        }
                    }
                }
            }

            // 3. Rappels aux invités (pour les invitations acceptées ou en attente)
            foreach ($event->getInvitations() as $invitation) {
                // Récupérer l'utilisateur depuis le participant
                $participant = $invitation->getParticipant();
                
                // Si l'invitation a un participant lié
                if ($participant && $participant->getEmail()) {
                    // Créer un utilisateur fictif avec les informations de l'invitation
                    $uniqueKey = $invitation->getId() . '_' . $event->getId();
                    
                    if (!in_array($uniqueKey, $usersNotified)) {
                        try {
                            // Créer un objet utilisateur temporaire pour l'email
                            $tempUser = new \stdClass();
                            $tempUser->email = $invitation->getEmail();
                            $tempUser->fullName = $invitation->getName();
                            
                            // Envoyer l'email directement sans utiliser EmailService
                            if (!$testMode) {
                                $this->sendReminderEmail($tempUser, $event);
                            }
                            
                            $usersNotified[] = $uniqueKey;
                            $eventReminders++;
                            $io->text(sprintf('   ✅ Rappel %senvoyé à l\'invité: %s', 
                                $testMode ? '(simulation) ' : '',
                                $invitation->getName()
                            ));
                        } catch (\Exception $e) {
                            $io->error(sprintf('   ❌ Erreur envoi rappel invité %s: %s', $invitation->getName(), $e->getMessage()));
                        }
                    }
                }
            }

            $totalReminders += $eventReminders;
            $io->text(sprintf('   📊 %d rappel(s) envoyé(s) pour cet événement', $eventReminders));
        }

        $io->success(sprintf('✅ Processus terminé: %d rappel(s) envoyé(s) au total pour %d événement(s)', 
            $totalReminders, 
            count($events)
        ));

        return Command::SUCCESS;
    }

    /**
     * Envoie un email de rappel personnalisé pour les invités
     */
    private function sendReminderEmail($user, $event): void
    {
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to($user->email)
            ->subject('⏰ Rappel : Événement à venir - ' . $event->getTitle())
            ->html($this->createReminderHtml($user, $event));

        $this->mailer->send($email);
    }

    /**
     * Crée le HTML du rappel
     */
    private function createReminderHtml($user, $event): string
    {
        return sprintf('
            <div style="font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #667eea;">⏰ Rappel d\'événement</h2>
                <p>Bonjour <strong>%s</strong>,</p>
                <p>Nous vous rappelons que vous avez un événement prévu pour <strong>demain</strong> :</p>
                
                <div style="background: #f8f9ff; padding: 20px; border-radius: 8px; border-left: 4px solid #667eea; margin: 20px 0;">
                    <h3 style="margin-top: 0; color: #333;">%s</h3>
                    <p><strong>📅 Date :</strong> %s</p>
                    <p><strong>🕐 Heure :</strong> %s</p>
                    <p><strong>🏢 Lieu :</strong> %s</p>
                    <p><strong>⏱️ Durée :</strong> %s minutes</p>
                    %s
                </div>
                
                <p>N\'oubliez pas de vous préparer pour cet événement.</p>
                
                <div style="font-size: 12px; color: #666; margin-top: 30px; text-align: center;">
                    <p>Cet email est envoyé automatiquement par <strong>EventHub</strong>.</p>
                </div>
            </div>
        ',
            htmlspecialchars($user->fullName),
            htmlspecialchars($event->getTitle()),
            $event->getDateHeure()->format('d/m/Y'),
            $event->getDateHeure()->format('H:i'),
            $event->getSalle() ? htmlspecialchars($event->getSalle()->getNom()) : 'Non défini',
            $event->getDuree(),
            $event->getDescription() ? '<p><strong>📝 Description :</strong> ' . htmlspecialchars($event->getDescription()) . '</p>' : ''
        );
    }
}
