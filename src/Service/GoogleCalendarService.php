<?php
// src/Service/GoogleCalendarService.php
namespace App\Service;

use Google\Client;
use Google\Service\Calendar;

class GoogleCalendarService
{
    private $client;

    public function __construct(string $clientId, string $clientSecret, string $redirectUri)
    {
        $this->client = new Client();
        $this->client->setClientId($clientId);
        $this->client->setClientSecret($clientSecret);
        $this->client->setRedirectUri($redirectUri);
        $this->client->addScope(Calendar::CALENDAR);
        $this->client->setAccessType('offline');
    }

    public function addEvent(string $token, array $eventData)
    {
        $this->client->setAccessToken($token);

        // Si le token est expiré, on doit le rafraîchir
        if ($this->client->isAccessTokenExpired()) {
            throw new \Exception("Token expired. Please reauthenticate.");
        }

        $service = new Calendar($this->client);

        $event = new Calendar\Event([
            'summary' => $eventData['summary'],
            'location' => $eventData['location'],
            'description' => $eventData['description'],
            'start' => [
                'dateTime' => $eventData['start_date_time'],
                'timeZone' => $eventData['time_zone'],
            ],
            'end' => [
                'dateTime' => $eventData['end_date_time'],
                'timeZone' => $eventData['time_zone'],
            ],
            'attendees' => $eventData['attendees'],
            'reminders' => [
                'useDefault' => false,
                'overrides' => [
                    ['method' => 'popup', 'minutes' => 10],
                ],
            ],
        ]);

        // Ajouter l'événement au calendrier
        $calendarId = 'primary'; // Utiliser le calendrier principal
        $event = $service->events->insert($calendarId, $event);

        return $event;
    }
}
