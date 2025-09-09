<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use App\Service\InvitationExpirationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class ForceExpirationCommand extends Command
{
    protected static $defaultName = 'app:force-expiration';
    protected static $defaultDescription = 'Force l\'expiration des invitations en attente';

    public function __construct(
        private InvitationRepository $invitationRepository,
        private InvitationExpirationService $expirationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        try {
            // Récupérer toutes les invitations en attente
            $pendingInvitations = $this->invitationRepository->findBy(['status' => 'pending']);
            
            $io->title('Forçage de l\'expiration des invitations');
            $io->progressStart(count($pendingInvitations));
            
            $expiredCount = 0;
            foreach ($pendingInvitations as $invitation) {
                $event = $invitation->getEvent();
                $eventCategory = $event ? $event->getCategory() : 'inconnu';
                
                // Forcer l'expiration
                if ($this->expirationService->checkAndExpireInvitation($invitation, 1)) {
                    $expiredCount++;
                    $io->note(sprintf(
                        'Invitation expirée - ID: %d, Email: %s, Catégorie: %s',
                        $invitation->getId(),
                        $invitation->getEmail(),
                        $eventCategory
                    ));
                }
                
                $io->progressAdvance();
            }
            
            $io->progressFinish();
            
            if ($expiredCount > 0) {
                $this->entityManager->flush();
                $io->success(sprintf('%d invitation(s) expirée(s)', $expiredCount));
            } else {
                $io->info('Aucune invitation n\'a été expirée');
            }
            
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
