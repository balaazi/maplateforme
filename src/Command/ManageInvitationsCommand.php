<?php

namespace App\Command;

use App\Service\InvitationManagementService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:manage-invitations',
    description: 'Gère automatiquement l\'expiration des invitations ET la détection des conflits d\'horaires',
)]
class ManageInvitationsCommand extends Command
{
    public function __construct(
        private InvitationManagementService $invitationManagementService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('days', 'd', InputOption::VALUE_OPTIONAL, 'Nombre de jours avant expiration (défaut: 30)', 30)
            ->addOption('user', 'u', InputOption::VALUE_REQUIRED, 'Email de l\'utilisateur spécifique à vérifier')
            ->addOption('event', 'e', InputOption::VALUE_REQUIRED, 'ID de l\'événement spécifique à vérifier')
            ->addOption('quiet', 'q', InputOption::VALUE_NONE, 'Mode silencieux (pas de sortie détaillée)')
            ->setHelp('Cette commande gère automatiquement l\'expiration des invitations et la détection des conflits d\'horaires.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $quiet = $input->getOption('quiet');
        $daysExpiration = (int) $input->getOption('days');

        if (!$quiet) {
            $io->title('🎯 Gestion Automatique des Invitations');
            $io->text('Gestion de l\'expiration ET détection des conflits d\'horaires...');
        }

        try {
            $userEmail = $input->getOption('user');
            $eventId = $input->getOption('event');

            if ($userEmail) {
                // Gestion pour un utilisateur spécifique
                if (!$quiet) {
                    $io->text("Gestion des invitations pour l'utilisateur : {$userEmail}");
                }
                
                $results = $this->invitationManagementService->manageInvitationsForUser($userEmail);
                
                if (!$quiet) {
                    if ($results['conflicts'] > 0) {
                        $io->success("{$results['conflicts']} conflit(s) détecté(s) et marqué(s) pour l'utilisateur {$userEmail}");
                    } else {
                        $io->info("Aucun conflit détecté pour l'utilisateur {$userEmail}");
                    }
                }
                
                return Command::SUCCESS;
            }

            if ($eventId) {
                // Gestion pour un événement spécifique
                if (!$quiet) {
                    $io->text("Gestion des invitations pour l'événement ID : {$eventId}");
                }
                
                $results = $this->invitationManagementService->manageInvitationsForEvent((int) $eventId);
                
                if (!$quiet) {
                    if ($results['conflicts'] > 0) {
                        $io->success("{$results['conflicts']} conflit(s) détecté(s) et marqué(s) pour l'événement ID {$eventId}");
                    } else {
                        $io->info("Aucun conflit détecté pour l'événement ID {$eventId}");
                    }
                }
                
                return Command::SUCCESS;
            }

            // Gestion globale
            if (!$quiet) {
                $io->text("Gestion globale des invitations (expiration après {$daysExpiration} jours)...");
                $io->text('Étape 1: Détection des conflits d\'horaires...');
            }
            
            $results = $this->invitationManagementService->manageInvitationsAutomatically($daysExpiration);
            
            if (!$quiet) {
                $io->text('Étape 2: Expiration des invitations anciennes...');
                
                if ($results['total_processed'] > 0) {
                    $io->success("Gestion terminée avec succès !");
                    $io->table(
                        ['Opération', 'Nombre'],
                        [
                            ['Conflits détectés', $results['conflicts']],
                            ['Invitations expirées', $results['expired']],
                            ['Total traité', $results['total_processed']]
                        ]
                    );
                } else {
                    $io->info('Aucune action nécessaire - toutes les invitations sont à jour');
                }
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            if (!$quiet) {
                $io->error('Erreur lors de la gestion des invitations : ' . $e->getMessage());
            }
            return Command::FAILURE;
        }
    }
}