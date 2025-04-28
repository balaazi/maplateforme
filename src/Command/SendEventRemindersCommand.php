<?php

namespace App\Command;

use App\Repository\EventRepository;
use App\Service\EmailService;
use App\Service\SmsService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:send-event-reminders',
    description: 'Envoie des rappels avant les événements.'
)]
class SendEventRemindersCommand extends Command
{
    public function __construct(
        private EventRepository $eventRepository,
        private EmailService $emailService,
        private SmsService $smsService,
        private EntityManagerInterface $em
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        // ✅ Récupère les événements de demain
        $tomorrow = (new \DateTime())->modify('+1 day')->setTime(0, 0);
        $afterTomorrow = (new \DateTime())->modify('+2 day')->setTime(0, 0);

        // ✅ Méthode personnalisée dans EventRepository
        $events = $this->eventRepository->findByDateRange($tomorrow, $afterTomorrow);

        foreach ($events as $event) {
            foreach ($event->getInvitations() as $invitation) {
                $user = $invitation->getUser();

                if ($user === null) {
                    continue;
                }

                // ✅ Envoie email si activé
                if ($user->isNotifyByEmail()) {
                    $this->emailService->sendReminder($user, $event);
                }

                // ✅ Envoie SMS si numéro dispo
                if ($user->getPhone()) {
                    $this->smsService->sendReminder($user, $event);
                }
            }
        }

        $output->writeln('✅ Rappels envoyés pour les événements de demain.');

        return Command::SUCCESS;
    }
}
