<?php

namespace App\Command;

use App\Service\AutomaticConflictDetectionService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:detect-conflicts',
    description: 'Détecte automatiquement les conflits d\'horaires et met à jour le statut des invitations',
)]
class DetectConflictsCommand extends Command
{
    public function __construct(
        private AutomaticConflictDetectionService $conflictDetectionService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Email de l\'utilisateur spécifique à vérifier')
            ->addOption('event', 'e', InputOption::VALUE_REQUIRED, 'ID de l\'événement spécifique à vérifier')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Mode silencieux (pas de sortie détaillée)')
            ->setHelp('Cette commande détecte automatiquement les conflits d\'horaires et met à jour le statut des invitations.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $quiet = $input->getOption('quiet');

        if (!$quiet) {
            $io->title('🔍 Détection Automatique des Conflits d\'Horaires');
            $io->text('Vérification des invitations en attente pour détecter les conflits...');
        }

        try {
            $userEmail = $input->getOption('user');
            $eventId = $input->getOption('event');

            if ($userEmail) {
                // Détection pour un utilisateur spécifique
                if (!$quiet) {
                    $io->text("Vérification des conflits pour l'utilisateur : {$userEmail}");
                }
                
                $conflictCount = $this->conflictDetectionService->detectConflictsForUser($userEmail);
                
                if (!$quiet) {
                    if ($conflictCount > 0) {
                        $io->success("{$conflictCount} conflit(s) détecté(s) et marqué(s) pour l'utilisateur {$userEmail}");
                    } else {
                        $io->info("Aucun conflit détecté pour l'utilisateur {$userEmail}");
                    }
                }
                
                return Command::SUCCESS;
            }

            if ($eventId) {
                // Détection pour un événement spécifique
                if (!$quiet) {
                    $io->text("Vérification des conflits pour l'événement ID : {$eventId}");
                }
                
                $conflictCount = $this->conflictDetectionService->detectConflictsForEvent((int) $eventId);
                
                if (!$quiet) {
                    if ($conflictCount > 0) {
                        $io->success("{$conflictCount} conflit(s) détecté(s) et marqué(s) pour l'événement ID {$eventId}");
                    } else {
                        $io->info("Aucun conflit détecté pour l'événement ID {$eventId}");
                    }
                }
                
                return Command::SUCCESS;
            }

            // Détection globale
            if (!$quiet) {
                $io->text('Vérification globale de tous les conflits...');
            }
            
            $conflictCount = $this->conflictDetectionService->detectAndMarkConflicts();
            
            if (!$quiet) {
                if ($conflictCount > 0) {
                    $io->success("{$conflictCount} conflit(s) détecté(s) et marqué(s) automatiquement");
                } else {
                    $io->info('Aucun conflit d\'horaires détecté');
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$quiet) {
                $io->error('Erreur lors de la détection des conflits : ' . $e->getMessage());
            }
            return Command::FAILURE;
        }
    }
}
