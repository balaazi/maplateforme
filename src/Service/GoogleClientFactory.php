<?php
// src/Service/GoogleClientFactory.php

namespace App\Service;

use Google_Client;

class GoogleClientFactory
{
    public function createClient(string $clientId, string $clientSecret, string $redirectUri): Google_Client
    {
        $client = new Google_Client();
        $client->setClientId($clientId);
        $client->setClientSecret($clientSecret);
        $client->setRedirectUri($redirectUri);

        // Ajouter des scopes spécifiques pour l'accès au calendrier
        $client->addScope([
            'https://www.googleapis.com/auth/calendar',
            'https://www.googleapis.com/auth/userinfo.email',
            'https://www.googleapis.com/auth/userinfo.profile',
        ]);

        $client->setAccessType('offline');
        $client->setPrompt('consent');

        return $client;
    }
}
