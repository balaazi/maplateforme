<?php

namespace App\Command;

use App\Service\ParticipationExpirationService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:expire-participations',
    description: 'Marque les participations en attente comme expirées après un délai spécifié',
)]
class ExpireParticipationsCommand extends Command
{
    public function __construct(
        private ParticipationExpirationService $expirationService
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
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $days = (int) $input->getOption('days');

        $io->title('Expiration des Participations');
        $io->text("Marquage des participations en attente comme expirées après {$days} jours...");

        try {
            $expiredCount = $this->expirationService->expireOldParticipations($days);
            
            if ($expiredCount > 0) {
                $io->success("Expiration terminée ! {$expiredCount} participations marquées comme expirées.");
            } else {
                $io->info('Aucune participation à expirer.');
            }
            
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $io->error('Erreur lors de l\'expiration : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
