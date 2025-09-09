<?php

namespace App\Command;

use App\Service\InvitationStatusMigrationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:migrate-invitation-statuses',
    description: 'Migre les anciens statuts d\'invitation vers les nouveaux',
)]
class MigrateInvitationStatusesCommand extends Command
{
    public function __construct(
        private InvitationStatusMigrationService $migrationService
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Migration des Statuts d\'Invitation');
        $io->text('Migration des anciens statuts vers les nouveaux...');

        // Vérifier s'il y a des anciens statuts
        if (!$this->migrationService->hasOldStatuses()) {
            $io->success('Aucun ancien statut à migrer. Tous les statuts sont déjà à jour !');
            return Command::SUCCESS;
        }

        // Afficher le compte des anciens statuts
        $oldStatusesCount = $this->migrationService->getOldStatusesCount();
        $io->section('Anciens statuts détectés :');
        
        foreach ($oldStatusesCount as $status => $count) {
            if ($count > 0) {
                $io->text("- {$status}: {$count} participations");
            }
        }

        // Demander confirmation
        if (!$io->confirm('Voulez-vous continuer avec la migration ?', true)) {
            $io->warning('Migration annulée.');
            return Command::SUCCESS;
        }

        // Exécuter la migration
        try {
            $migratedCount = $this->migrationService->migrateOldStatuses();
            
            if ($migratedCount > 0) {
                $io->success("Migration terminée avec succès ! {$migratedCount} participations migrées.");
            } else {
                $io->warning('Aucune participation migrée.');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Erreur lors de la migration : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
