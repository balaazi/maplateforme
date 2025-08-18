<?php
// src/Security/AccessDeniedHandler.php
namespace App\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\RouterInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\Security\Http\Authorization\AccessDeniedHandlerInterface;

class AccessDeniedHandler implements AccessDeniedHandlerInterface
{
    private RouterInterface $router;

    public function __construct(RouterInterface $router)
    {
        $this->router = $router;
    }

    public function handle(Request $request, AccessDeniedException $exception): Response
    {
        $route = $request->attributes->get('_route');
        $message = $exception->getMessage();
        
        // Analyser le type d'erreur d'accès
        if (strpos($message, 'ROLE_ORGANISATEUR') !== false) {
            return new RedirectResponse($this->router->generate('calendar_index'));
        }
        
        if (strpos($message, 'ROLE_ADMIN') !== false) {
            return new RedirectResponse($this->router->generate('app_home'));
        }
        
        if (strpos($message, 'ROLE_PARTICIPANT') !== false) {
            return new RedirectResponse($this->router->generate('app_home'));
        }
        
        // Erreurs liées aux événements
        if (strpos($route, 'event') !== false) {
            return new RedirectResponse($this->router->generate('event_list'));
        }
        
        // Erreurs liées à l'administration
        if (strpos($route, 'admin') !== false) {
            return new RedirectResponse($this->router->generate('app_home'));
        }
        
        // Erreurs liées aux salles
        if (strpos($route, 'salle') !== false || strpos($route, 'gestion-salle') !== false) {
            return new RedirectResponse($this->router->generate('calendar_index'));
        }
        
        // Redirection vers la page d'erreur personnalisée avec les détails
        $errorUrl = $this->router->generate('error_access_denied', [
            'message' => urlencode($message),
            'route' => $route
        ]);
        
        return new RedirectResponse($errorUrl);
    }
}
