<?php

namespace App\Command;

use App\Service\InvitationExpirationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:expire-invitations',
    description: 'Marque les anciennes invitations en attente comme expirées',
)]
class ExpireInvitationsCommand extends Command
{
    public function __construct(
        private InvitationExpirationService $expirationService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'days',
                'd',
                InputOption::VALUE_OPTIONAL,
                'Nombre de jours avant expiration (défaut: 30)',
                30
            )
            ->setHelp('Cette commande marque automatiquement les invitations en attente comme expirées après un délai spécifié.');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        
        $days = (int) $input->getOption('days');
        
        $io->title('Expiration des invitations');
        $io->text("Marquage des invitations en attente comme expirées après {$days} jours...");
        
        try {
            $count = $this->expirationService->expireOldInvitations($days);
            
            if ($count > 0) {
                $io->success("{$count} invitations ont été marquées comme expirées.");
            } else {
                $io->info('Aucune invitation à expirer trouvée.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'expiration des invitations: ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
