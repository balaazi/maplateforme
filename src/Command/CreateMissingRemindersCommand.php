<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Repository\ReminderRepository;
use App\Service\ReminderService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-missing-reminders',
    description: 'Crée automatiquement les rappels manquants pour tous les événements existants'
)]
class CreateMissingRemindersCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private ReminderRepository $reminderRepository,
        private ReminderService $reminderService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('future-only', null, InputOption::VALUE_NONE, 'Créer des rappels uniquement pour les événements futurs')
            ->addOption('dry-run', null, InputOption::VALUE_NONE, 'Afficher ce qui serait fait sans réellement créer les rappels')
            ->addOption('days-ahead', null, InputOption::VALUE_OPTIONAL, 'Créer des rappels pour les X prochains jours', 30)
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $isDryRun = $input->getOption('dry-run');
        $futureOnly = $input->getOption('future-only');
        $daysAhead = (int)$input->getOption('days-ahead');

        $io->title('🔔 Création des rappels automatiques manquants');

        if ($isDryRun) {
            $io->warning('Mode test activé - aucune action ne sera effectuée');
        }

        // Récupérer les événements
        $now = new \DateTime();
        $qb = $this->eventRepository->createQueryBuilder('e');

        if ($futureOnly) {
            $futureLimit = (clone $now)->modify("+{$daysAhead} days");
            $qb->where('e.dateHeure > :now')
               ->andWhere('e.dateHeure <= :future')
               ->setParameter('now', $now)
               ->setParameter('future', $futureLimit);
            
            $io->note(sprintf('Recherche d\'événements entre %s et %s', 
                $now->format('d/m/Y H:i'), 
                $futureLimit->format('d/m/Y H:i')
            ));
        } else {
            $io->note('Recherche de tous les événements');
        }

        $qb->andWhere('e.status IS NULL OR e.status != :cancelled')
           ->setParameter('cancelled', 'annulé')
           ->orderBy('e.dateHeure', 'ASC');

        $events = $qb->getQuery()->getResult();

        if (empty($events)) {
            $io->success('Aucun événement trouvé.');
            return Command::SUCCESS;
        }

        $io->section(sprintf('Traitement de %d événement(s)', count($events)));

        $totalRemindersCreated = 0;
        $eventsProcessed = 0;
        $errors = [];

        foreach ($events as $event) {
            $io->text(sprintf('📅 Événement: %s (%s)', 
                $event->getTitle(), 
                $event->getDateHeure()->format('d/m/Y H:i')
            ));

            try {
                // Vérifier s'il y a déjà des rappels pour cet événement
                $existingReminders = $this->reminderRepository->findRemindersByEvent($event);
                
                if (count($existingReminders) > 0) {
                    $io->text(sprintf('   ⚠️  %d rappel(s) existant(s) - ignoré', count($existingReminders)));
                    continue;
                }

                // Créer les rappels manquants
                if (!$isDryRun) {
                    // Rappels à 24h, 2h et 30 minutes avant l'événement
                    $reminderSchedule = [];
                    
                    // 24 heures avant (seulement si l'événement est dans plus de 24h)
                    $twentyFourHoursBefore = (clone $event->getDateHeure())->modify('-24 hours');
                    if ($twentyFourHoursBefore > $now) {
                        $reminderSchedule[] = 1440; // 24 heures en minutes
                    }
                    
                    // 2 heures avant (seulement si l'événement est dans plus de 2h)
                    $twoHoursBefore = (clone $event->getDateHeure())->modify('-2 hours');
                    if ($twoHoursBefore > $now) {
                        $reminderSchedule[] = 120; // 2 heures en minutes
                    }
                    
                    // 30 minutes avant (seulement si l'événement est dans plus de 30 min)
                    $thirtyMinsBefore = (clone $event->getDateHeure())->modify('-30 minutes');
                    if ($thirtyMinsBefore > $now) {
                        $reminderSchedule[] = 30; // 30 minutes
                    }

                    if (empty($reminderSchedule)) {
                        $io->text('   ⏰ Événement trop proche - aucun rappel créé');
                        continue;
                    }

                    $reminders = $this->reminderService->createReminderSchedule($event, $reminderSchedule);
                    $totalRemindersCreated += count($reminders);
                    
                    $io->text(sprintf('   ✅ %d rappel(s) créé(s)', count($reminders)));
                } else {
                    // Mode dry-run
                    $potentialReminders = [];
                    
                    $twentyFourHoursBefore = (clone $event->getDateHeure())->modify('-24 hours');
                    if ($twentyFourHoursBefore > $now) {
                        $potentialReminders[] = '24h avant';
                    }
                    
                    $twoHoursBefore = (clone $event->getDateHeure())->modify('-2 hours');
                    if ($twoHoursBefore > $now) {
                        $potentialReminders[] = '2h avant';
                    }
                    
                    $thirtyMinsBefore = (clone $event->getDateHeure())->modify('-30 minutes');
                    if ($thirtyMinsBefore > $now) {
                        $potentialReminders[] = '30min avant';
                    }

                    if (count($potentialReminders) > 0) {
                        $io->text(sprintf('   📋 %d rappel(s) seraient créés: %s', 
                            count($potentialReminders), 
                            implode(', ', $potentialReminders)
                        ));
                        $totalRemindersCreated += count($potentialReminders);
                    } else {
                        $io->text('   ⏰ Événement trop proche - aucun rappel ne serait créé');
                    }
                }

                $eventsProcessed++;

            } catch (\Exception $e) {
                $error = sprintf('Erreur pour événement %d: %s', $event->getId(), $e->getMessage());
                $errors[] = $error;
                $io->error($error);
            }
        }

        // Résumé
        $io->section('📊 Résumé');
        
        if ($isDryRun) {
            $io->success(sprintf(
                '%d événement(s) traité(s) - %d rappel(s) seraient créés',
                $eventsProcessed,
                $totalRemindersCreated
            ));
        } else {
            $io->success(sprintf(
                '%d événement(s) traité(s) - %d rappel(s) créé(s)',
                $eventsProcessed,
                $totalRemindersCreated
            ));
        }

        if (!empty($errors)) {
            $io->warning(sprintf('%d erreur(s) rencontrée(s):', count($errors)));
            foreach ($errors as $error) {
                $io->text('   - ' . $error);
            }
        }

        // Recommandations
        if (!$isDryRun && $totalRemindersCreated > 0) {
            $io->section('💡 Recommandations');
            $io->text([
                '• Configurez un CRON pour traiter les rappels automatiquement :',
                '  */5 * * * * php bin/console app:process-reminders',
                '',
                '• Vérifiez les préférences utilisateur pour les notifications :',
                '  - Notifications par email activées',
                '  - Notifications visuelles activées',
                '  - Sons de notification selon préférences',
                '',
                '• Testez le système avec :',
                '  php bin/console app:process-reminders --dry-run',
            ]);
        }

        return Command::SUCCESS;
    }
} 