<?php

namespace App\Controller;

use App\Service\GoogleDriveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class GoogleDriveController extends AbstractController
{
    #[Route('/google-drive/connect', name: 'google_drive_connect')]
    public function connect(GoogleDriveService $googleDriveService): Response
    {
        $client = $googleDriveService->getClient();
        $authUrl = $client->createAuthUrl();

        return $this->redirect($authUrl);
    }

    #[Route('/google-drive/callback', name: 'google_drive_callback')]
    public function callback(Request $request, GoogleDriveService $googleDriveService): Response
    {
        $code = $request->query->get('code');

        if (!$code) {
            return new Response('Code de vérification manquant', 400);
        }

        $token = $googleDriveService->authenticate($code);
        // Tu peux stocker ce token en base de données ou session

        return new Response('<h1>Connexion réussie à Google Drive ✅</h1><pre>' . print_r($token, true) . '</pre>');
    }
}
