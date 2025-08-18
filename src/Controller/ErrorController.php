<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;

class ErrorController extends AbstractController
{
    #[Route('/error/access-denied', name: 'error_access_denied')]
    public function accessDenied(Request $request): Response
    {
        $route = $request->attributes->get('_route');
        $message = $request->query->get('message', 'Accès refusé');
        
        // Analyser le type d'erreur pour fournir des suggestions appropriées
        $suggestions = $this->getSuggestions($route, $message);
        
        return $this->render('security/access_denied.html.twig', [
            'route' => $route,
            'message' => $message,
            'suggestions' => $suggestions,
        ]);
    }
    
    private function getSuggestions(string $route, string $message): array
    {
        $suggestions = [];
        
        if (strpos($message, 'ROLE_ORGANISATEUR') !== false) {
            $suggestions[] = 'Cette fonctionnalité nécessite le rôle d\'organisateur.';
            $suggestions[] = 'Contactez un administrateur pour obtenir les permissions nécessaires.';
        } elseif (strpos($message, 'ROLE_ADMIN') !== false) {
            $suggestions[] = 'Cette fonctionnalité est réservée aux administrateurs.';
        } elseif (strpos($message, 'ROLE_PARTICIPANT') !== false) {
            $suggestions[] = 'Cette fonctionnalité nécessite le rôle de participant.';
        }
        
        if (strpos($route, 'event') !== false) {
            $suggestions[] = 'Vous pouvez consulter la liste des événements publics.';
        }
        
        if (strpos($route, 'admin') !== false) {
            $suggestions[] = 'L\'accès à l\'administration est réservé aux administrateurs.';
        }
        
        if (strpos($route, 'salle') !== false || strpos($route, 'gestion-salle') !== false) {
            $suggestions[] = 'La gestion des salles est réservée aux organisateurs.';
            $suggestions[] = 'Vous pouvez consulter le calendrier pour voir les événements.';
        }
        
        return $suggestions;
    }
} 