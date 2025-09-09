<?php

namespace App\Controller;

use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

class NotificationPreferencesController extends AbstractController
{
    #[Route('/preferences/notifications/disable-email', name: 'disable_email_notifications', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function disableEmailNotifications(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $user->setNotifyByEmail(false);
        $user->setUpdatedAt(new \DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Les notifications par e-mail ont été désactivées.');
        
        // Rediriger vers la page des préférences
        return $this->redirectToRoute('user_preferences');
    }

    #[Route('/preferences/notifications/enable-email', name: 'enable_email_notifications', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function enableEmailNotifications(EntityManagerInterface $entityManager): Response
    {
        $user = $this->getUser();
        $user->setNotifyByEmail(true);
        $user->setUpdatedAt(new \DateTime());
        
        $entityManager->flush();
        
        $this->addFlash('success', 'Les notifications par e-mail ont été activées.');
        
        // Rediriger vers la page des préférences
        return $this->redirectToRoute('user_preferences');
    }

    #[Route('/preferences', name: 'user_preferences', methods: ['GET'])]
    #[IsGranted('ROLE_USER')]
    public function preferences(): Response
    {
        return $this->render('user/preferences.html.twig', [
            'user' => $this->getUser()
        ]);
    }
}
