<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class UpdateInvitationDatesCommand extends Command
{
    protected static $defaultName = 'app:update-invitation-dates';
    protected static $defaultDescription = 'Met à jour les dates des invitations en attente';

    public function __construct(
        private InvitationRepository $invitationRepository,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Récupérer les invitations en attente
            $pendingInvitations = $this->invitationRepository->findBy(['status' => 'pending']);
            
            $updatedCount = 0;
            $twoDaysAgo = new \DateTime('-2 days');
            
            foreach ($pendingInvitations as $invitation) {
                $invitation->setCreatedAt($twoDaysAgo);
                $updatedCount++;
                
                $io->text(sprintf(
                    "Mise à jour de l'invitation - ID: %d, Email: %s, Nouvelle date: %s",
                    $invitation->getId(),
                    $invitation->getEmail(),
                    $twoDaysAgo->format('Y-m-d H:i:s')
                ));
            }
            
            if ($updatedCount > 0) {
                $this->entityManager->flush();
                $io->success(sprintf('%d invitation(s) mise(s) à jour', $updatedCount));
            } else {
                $io->info('Aucune invitation à mettre à jour.');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
