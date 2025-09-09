<?php
namespace App\Service;

use App\Entity\User;
use App\Entity\Event;
use App\Entity\Invitation;
use Symfony\Component\Mime\Email;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

class MailerService
{
private MailerInterface $mailer;
private UrlGeneratorInterface $router;

public function __construct(MailerInterface $mailer, UrlGeneratorInterface $router)
{
$this->mailer = $mailer;
$this->router = $router;
}

// ✅ Envoi d'une invitation avec lien
public function sendInvitationEmail(string $toEmail, string $token): void
{
// Génération du lien d'inscription
$link = $this->router->generate('register', ['token' => $token], UrlGeneratorInterface::ABSOLUTE_URL);

// Création de l'email d'invitation
$email = (new Email())
->from('nadiabalaazi@gmail.com')
->to($toEmail)
->subject('Invitation à s’inscrire')
->html("<p>Bonjour,</p><p>Vous avez été invité à vous inscrire. Cliquez sur le lien suivant :</p><p><a href='$link'>Inscription</a></p>");

// Envoi de l'email
try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email d'invitation: " . $e->getMessage());
}
}

// ✅ Envoi d'un email de rappel automatique
public function sendReminderEmail(User $guest, Event $event): void
{
$email = (new TemplatedEmail())
->from('nadiabalaazi@gmail.com')
->to($guest->getEmail())
->subject("⏰ Rappel : " . $event->getTitle())
->htmlTemplate('emails/reminder.html.twig')  // Template corrigé pour les rappels
->context([
'event' => $event,
'user' => $guest,
]);

// Envoi de l'email
try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email de rappel: " . $e->getMessage());
}
}

// ✅ Envoi d'un email de rappel avancé (24h ou 1h avant)
public function sendAdvancedReminderEmail(User $user, Event $event, string $reminderType): void
{
$hoursBefore = $reminderType === '24h' ? 24 : 1;
$subject = $reminderType === '24h' ? '⏰ Rappel 24h - ' : '🚨 Rappel 1h - ';
$subject .= $event->getTitle();
    
$email = (new TemplatedEmail())
->from('nadiabalaazi@gmail.com')
->to($user->getEmail())
->subject($subject)
->htmlTemplate('emails/reminder_advanced.html.twig')
->context([
'event' => $event,
'user' => $user,
'reminder_type' => $reminderType,
'hours_before' => $hoursBefore,
'event_date' => $event->getDateHeure(),
'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
'event_duration' => $event->getDuree(),
'event_description' => $event->getDescription()
]);

try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email de rappel avancé: " . $e->getMessage());
}
}

// ✅ Envoi d'un email de rappel avancé à un invité
public function sendAdvancedReminderEmailToInvitee(Invitation $invitation, Event $event, string $reminderType): void
{
$hoursBefore = $reminderType === '24h' ? 24 : 1;
$subject = $reminderType === '24h' ? '⏰ Rappel 24h - ' : '🚨 Rappel 1h - ';
$subject .= $event->getTitle();
    
// Créer un objet utilisateur temporaire pour l'email
$tempUser = new \stdClass();
$tempUser->email = $invitation->getEmail();
$tempUser->fullName = $invitation->getName();
$tempUser->prenom = explode(' ', $invitation->getName())[0];
    
$email = (new TemplatedEmail())
->from('nadiabalaazi@gmail.com')
->to($invitation->getEmail())
->subject($subject)
->htmlTemplate('emails/reminder_advanced.html.twig')
->context([
'event' => $event,
'user' => $tempUser,
'reminder_type' => $reminderType,
'hours_before' => $hoursBefore,
'event_date' => $event->getDateHeure(),
'event_location' => $event->getSalle()?->getNom() ?? 'Non défini',
'event_duration' => $event->getDuree(),
'event_description' => $event->getDescription()
]);

try {
$this->mailer->send($email);
} catch (\Exception $e) {
throw new \RuntimeException("Échec de l'envoi de l'email de rappel avancé à l'invité: " . $e->getMessage());
}
}
}
