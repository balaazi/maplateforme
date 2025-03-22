<?php
use Symfony\Component\Mime\Email;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;

class EmailService
{
    private MailerInterface $mailer;
    private MessageBusInterface $bus;

    public function __construct(MailerInterface $mailer, MessageBusInterface $bus)
    {
        $this->mailer = $mailer;
        $this->bus = $bus;
    }

    public function sendInvitation(string $emailTo)
    {
        $email = (new Email())
            ->from('nadiabalaazi@gmail.com')
            ->to($emailTo)
            ->subject('Invitation à s\'inscrire')
            ->html("<p>Vous êtes invité à vous inscrire. Cliquez ici : <a href='http://127.0.0.1:8000/register'>S'inscrire</a></p>");

        $this->bus->dispatch(new SendEmailMessage($email));
    }
}
