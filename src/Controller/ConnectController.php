<?php

namespace App\Controller;

use App\Service\GoogleCalendarService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class ConnectController extends AbstractController
{
    #[Route('/connect/google', name: 'google_connect')]
    public function connect(GoogleCalendarService $googleCalendarService): Response
    {
        $authUrl = $googleCalendarService->getAuthUrl();
        return $this->redirect($authUrl);
    }

    #[Route('/connect/google/callback', name: 'google_callback')]
    public function callback(Request $request, GoogleCalendarService $googleCalendarService): Response
    {
        $code = $request->query->get('code');
        if (!$code) {
            return new Response('Code manquant dans la requête.', 400);
        }

        try {
            $googleCalendarService->fetchAccessTokenWithCode($code);
            return new Response('Connexion à Google Calendar réussie. Vous pouvez maintenant utiliser la commande !');
        } catch (\Exception $e) {
            return new Response('Erreur: ' . $e->getMessage(), 500);
        }
    }
}
