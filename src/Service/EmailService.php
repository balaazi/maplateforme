<?php
// src/Service/EmailService.php

namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;

class EmailService
{
private MailerInterface $mailer;

public function __construct(MailerInterface $mailer)
{
$this->mailer = $mailer;
}

public function sendReminder(User $user, Event $event): void
{
// Utiliser la méthode getFullName() pour obtenir le nom complet de l'utilisateur
$fullName = $user->getFullName();

$email = (new Email())
->from('nadiabalaazi@gmail.com') // Remplace par ton email valide
->to($user->getEmail())
->subject('⏰ Rappel : Événement à venir')
->html("
<p>Bonjour {$fullName},</p>
<p>Vous avez un événement demain : <strong>{$event->getTitle()}</strong></p>
<p>Lieu : {$event->getLieu()}</p>
<p>Date & Heure : {$event->getDateHeure()->format('d/m/Y H:i')}</p>
");

$this->mailer->send($email);  // Envoie l'email
}
}
