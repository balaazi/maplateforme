<?php

namespace App\Command;

use App\Entity\Event;
use App\Entity\User;
use App\Repository\EventRepository;
use App\Repository\UserRepository;
use App\Service\EventNotificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:test-event-notifications',
    description: 'Teste le système de notifications d\'événements (modification et annulation)'
)]
class TestEventNotificationsCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private UserRepository $userRepository,
        private EventNotificationService $eventNotificationService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('event-id', null, InputOption::VALUE_OPTIONAL, 'ID de l\'événement à utiliser pour le test')
            ->addOption('user-email', null, InputOption::VALUE_OPTIONAL, 'Email de l\'utilisateur pour le test')
            ->addOption('type', null, InputOption::VALUE_OPTIONAL, 'Type de test (update/cancel)', 'update')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Mode test - afficher sans envoyer')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $eventId = $input->getOption('event-id');
        $userEmail = $input->getOption('user-email');
        $testType = $input->getOption('type');
        $isDryRun = $input->getOption('dry-run');

        $io->title('🧪 Test du Système de Notifications d\'Événements');

        if ($isDryRun) {
            $io->warning('Mode test activé - aucun email ne sera envoyé');
        }

        // 1. Sélectionner un événement
        if ($eventId) {
            $event = $this->eventRepository->find($eventId);
            if (!$event) {
                $io->error("Événement avec l'ID {$eventId} non trouvé");
                return Command::FAILURE;
            }
        } else {
            // Prendre le premier événement futur disponible
            $events = $this->eventRepository->createQueryBuilder('e')
                ->where('e.dateHeure > :now')
                ->andWhere('e.status IS NULL OR e.status != :cancelled')
                ->setParameter('now', new \DateTime())
                ->setParameter('cancelled', 'annulé')
                ->orderBy('e.dateHeure', 'ASC')
                ->setMaxResults(1)
                ->getQuery()
                ->getResult();

            if (empty($events)) {
                $io->error('Aucun événement futur trouvé pour le test');
                return Command::FAILURE;
            }

            $event = $events[0];
        }

        $io->section(sprintf('Événement sélectionné : %s (ID: %d)', $event->getTitle(), $event->getId()));
        $io->text([
            sprintf('📅 Date : %s', $event->getDateHeure()->format('d/m/Y H:i')),
            sprintf('👤 Organisateur : %s', $event->getOrganizer() ? $event->getOrganizer()->getFullName() : 'Non défini'),
            sprintf('👥 Participants : %d', count($event->getParticipations())),
            sprintf('📧 Invitations : %d', count($event->getInvitations())),
        ]);

        // 2. Vérifier les préférences utilisateur
        $io->section('📋 Vérification des préférences de notification');

        $usersToNotify = [];
        
        // Organisateur
        $organizer = $event->getOrganizer();
        if ($organizer) {
            $emailEnabled = $organizer->isNotifyByEmail() ? '✅ Activé' : '❌ Désactivé';
            $io->text(sprintf('👤 Organisateur %s - Emails: %s', $organizer->getFullName(), $emailEnabled));
            if ($organizer->isNotifyByEmail()) {
                $usersToNotify[] = ['type' => 'Organisateur', 'user' => $organizer];
            }
        }

        // Participants
        foreach ($event->getParticipations() as $participation) {
            $user = $participation->getUser();
            if ($user) {
                $emailEnabled = $user->isNotifyByEmail() ? '✅ Activé' : '❌ Désactivé';
                $io->text(sprintf('👥 Participant %s - Emails: %s', $user->getFullName(), $emailEnabled));
                if ($user->isNotifyByEmail()) {
                    $usersToNotify[] = ['type' => 'Participant', 'user' => $user];
                }
            }
        }

        // Invités
        foreach ($event->getInvitations() as $invitation) {
            if ($invitation->getEmail()) {
                $participantName = $invitation->getParticipant() ? 
                    $invitation->getParticipant()->getFullName() : 'Invité(e)';
                $io->text(sprintf('📧 Invité %s (%s)', $participantName, $invitation->getEmail()));
                $usersToNotify[] = ['type' => 'Invité', 'email' => $invitation->getEmail(), 'name' => $participantName];
            }
        }

        if (empty($usersToNotify)) {
            $io->warning('Aucun utilisateur ne recevra de notifications (préférences désactivées)');
        } else {
            $io->success(sprintf('%d utilisateur(s) recevront des notifications', count($usersToNotify)));
        }

        // 3. Test spécifique utilisateur si demandé
        if ($userEmail) {
            $testUser = $this->userRepository->findOneBy(['email' => $userEmail]);
            if (!$testUser) {
                $io->error("Utilisateur avec l'email {$userEmail} non trouvé");
                return Command::FAILURE;
            }
            
            $io->section(sprintf('🎯 Test spécifique pour %s', $testUser->getFullName()));
            
            if (!$testUser->isNotifyByEmail()) {
                $io->warning('Attention: Les notifications par email sont désactivées pour cet utilisateur');
                
                if ($io->confirm('Activer temporairement les notifications pour le test ?')) {
                    $testUser->setNotifyByEmail(true);
                    $this->em->flush();
                    $io->success('Notifications activées temporairement');
                }
            }
        }

        // 4. Exécuter le test
        $io->section(sprintf('🚀 Exécution du test : %s', $testType === 'update' ? 'Modification' : 'Annulation'));

        if (!$isDryRun) {
            try {
                if ($testType === 'update') {
                    $this->eventNotificationService->sendEventUpdateNotification($event);
                    $io->success('Notifications de modification envoyées avec succès !');
                } else {
                    $this->eventNotificationService->sendEventCancelNotification($event);
                    $io->success('Notifications d\'annulation envoyées avec succès !');
                }

                $io->section('📊 Résultats');
                $io->text([
                    '✅ Service de notification exécuté',
                    '📧 Emails envoyés selon les préférences utilisateur',
                    '🔔 Notifications en base de données créées',
                    '📝 Logs détaillés enregistrés',
                ]);

            } catch (\Exception $e) {
                $io->error('Erreur lors de l\'envoi des notifications : ' . $e->getMessage());
                return Command::FAILURE;
            }
        } else {
            $io->info('Mode dry-run : Les notifications suivantes seraient envoyées :');
            
            foreach ($usersToNotify as $recipient) {
                if (isset($recipient['user'])) {
                    $io->text(sprintf('📧 → %s (%s) : %s', 
                        $recipient['user']->getFullName(),
                        $recipient['user']->getEmail(),
                        $recipient['type']
                    ));
                } else {
                    $io->text(sprintf('📧 → %s (%s) : %s', 
                        $recipient['name'],
                        $recipient['email'],
                        $recipient['type']
                    ));
                }
            }
        }

        // 5. Vérifications post-test
        if (!$isDryRun) {
            $io->section('🔍 Vérifications');
            
            $io->text([
                '1. Vérifiez votre boîte email (et dossier spam)',
                '2. Consultez les logs : var/log/dev.log',
                '3. Vérifiez les notifications en base de données',
            ]);

            if ($io->confirm('Afficher les logs récents des notifications ?')) {
                $this->showRecentLogs($io);
            }
        }

        // 6. Conseils d'amélioration
        $io->section('💡 Conseils');
        $io->text([
            '• Activez les notifications par email dans les préférences utilisateur',
            '• Vérifiez la configuration SMTP dans .env',
            '• Surveillez les logs pour détecter les erreurs d\'envoi',
            '• Testez avec différents types d\'utilisateurs (organisateur/participant)',
        ]);

        return Command::SUCCESS;
    }

    private function showRecentLogs(SymfonyStyle $io): void
    {
        $logFile = 'var/log/dev.log';
        
        if (!file_exists($logFile)) {
            $io->warning('Fichier de log non trouvé');
            return;
        }

        $io->text('📄 Logs récents (dernières 10 lignes avec "notification") :');
        
        try {
            $command = "tail -n 50 {$logFile} | grep -i 'notification\|email' | tail -n 10";
            $output = shell_exec($command);
            
            if ($output) {
                $lines = explode("\n", trim($output));
                foreach ($lines as $line) {
                    if (!empty($line)) {
                        $io->text('  ' . $line);
                    }
                }
            } else {
                $io->text('  Aucun log récent trouvé');
            }
        } catch (\Exception $e) {
            $io->warning('Impossible de lire les logs : ' . $e->getMessage());
        }
    }
} 