<?php
// src/Service/SmsService.php
namespace App\Service;

use Twilio\Rest\Client;

class SmsService
{
private Client $twilioClient;
private string $from;

// Injection des dépendances via le constructeur
public function __construct(string $sid, string $authToken, string $from)
{
$this->twilioClient = new Client($sid, $authToken);
$this->from = $from;
}

// Fonction d'envoi de SMS
public function sendSms(string $to, string $message): void
{
$this->twilioClient->messages->create(
$to,
[
'from' => $this->from,
'body' => $message,
]
);
}
}
