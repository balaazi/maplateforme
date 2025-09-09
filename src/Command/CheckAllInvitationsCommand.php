<?php

namespace App\Command;

use App\Repository\InvitationRepository;
use App\Service\InvitationExpirationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

class CheckAllInvitationsCommand extends Command
{
    protected static $defaultName = 'app:check-all-invitations';
    protected static $defaultDescription = 'Vérifie toutes les invitations pour tous les types d\'événements';

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
            
            $io->title('Vérification des invitations par type d\'événement');
            
            // Grouper les invitations par type d'événement
            $groupedInvitations = [];
            foreach ($pendingInvitations as $invitation) {
                $event = $invitation->getEvent();
                $type = $event ? $event->getType() : 'inconnu';
                $groupedInvitations[$type][] = $invitation;
            }
            
            // Traiter chaque groupe
            foreach ($groupedInvitations as $type => $invitations) {
                $io->section(sprintf('Type d\'événement : %s', $type));
                $io->progressStart(count($invitations));
                
                $expiredCount = 0;
                foreach ($invitations as $invitation) {
                    if ($this->expirationService->checkAndExpireInvitation($invitation, 1)) {
                        $expiredCount++;
                    }
                    $io->progressAdvance();
                }
                
                $io->progressFinish();
                $io->text(sprintf(
                    '%d invitation(s) sur %d expirée(s) pour le type %s',
                    $expiredCount,
                    count($invitations),
                    $type
                ));
            }
            
            // Sauvegarder les changements
            $this->entityManager->flush();
            
            $io->success('Vérification terminée');
            return Command::SUCCESS;
        } catch (\Exception $e) {
            $io->error($e->getMessage());
            return Command::FAILURE;
        }
    }
}
