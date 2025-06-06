<?php

namespace App\Controller;

use App\Service\GoogleDriveService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class GoogleDriveController extends AbstractController
{
    #[Route('/google-drive/auth', name: 'google_drive_connect')]
    public function connect(GoogleDriveService $driveService): Response
    {
        $client = $driveService->getClient();
        $authUrl = $client->createAuthUrl();

        return $this->redirect($authUrl);
    }

    #[Route('/google-drive/callback', name: 'google_drive_callback')]
    public function callback(Request $request, GoogleDriveService $driveService, SessionInterface $session): Response
    {
        $client = $driveService->getClient();
        $code = $request->query->get('code');

        if ($code) {
            try {
                $accessToken = $client->fetchAccessTokenWithAuthCode($code);
                $session->set('google_access_token', $accessToken);

                $this->addFlash('success', 'Connexion à Google Drive réussie.');
                return $this->redirectToRoute('event_list');
            } catch (\Exception $e) {
                $this->addFlash('error', 'Erreur lors de l\'authentification avec Google: ' . $e->getMessage());
            }
        }

        $this->addFlash('error', 'Erreur lors de l\'authentification avec Google.');
        return $this->redirectToRoute('event_list');
    }
}
