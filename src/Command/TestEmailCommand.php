<?php
// src/Command/TestEmailCommand.php

namespace App\Command;

use App\Service\EmailService;
use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
name: 'app:test-email',
description: 'Teste l\'envoi d\'un email de rappel.',
)]
class TestEmailCommand extends Command
{
private EmailService $emailService;

public function __construct(EmailService $emailService)
{
parent::__construct();
$this->emailService = $emailService;
}

protected function execute(InputInterface $input, OutputInterface $output): int
{
// Simuler un utilisateur et un événement pour le test
$user = new User();
$user->setNom('Balaazi')
->setPrenom('Nadia')
->setEmail('nadiabalaazi@gmail.com');

$event = new Event();  // Remplace par ton objet Event réel
$event->setTitle('Formation PHP')
->setLieu('Salle B')
->setDateHeure(new \DateTime('tomorrow 11:00'));

// Envoi de l'email
try {
$this->emailService->sendReminder($user, $event);
$output->writeln('<info>✅ Email de test envoyé avec succès !</info>');
} catch (\Exception $e) {
$output->writeln('<error>❌ Erreur lors de l\'envoi de l\'email : ' . $e->getMessage() . '</error>');
return Command::FAILURE;
}

return Command::SUCCESS;
}
}
