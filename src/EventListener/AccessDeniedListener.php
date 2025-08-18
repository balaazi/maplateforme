<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Routing\RouterInterface;

class AccessDeniedListener implements EventSubscriberInterface
{
    private LoggerInterface $logger;
    private RouterInterface $router;

    public function __construct(LoggerInterface $logger, RouterInterface $router)
    {
        $this->logger = $logger;
        $this->router = $router;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => 'onKernelException',
        ];
    }

    public function onKernelException(ExceptionEvent $event): void
    {
        $exception = $event->getThrowable();
        
        if ($exception instanceof AccessDeniedHttpException) {
            $request = $event->getRequest();
            $route = $request->attributes->get('_route');
            $message = $exception->getMessage();
            
            // Logger les détails de l'erreur d'accès
            $this->logger->error('Access Denied Error Details', [
                'route' => $route,
                'controller' => $request->attributes->get('_controller'),
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'referer' => $request->headers->get('referer'),
                'is_ajax' => $request->isXmlHttpRequest(),
                'message' => $message,
                'trace' => $exception->getTraceAsString()
            ]);

            // Gestion spécifique selon le type d'erreur
            if (strpos($message, 'ROLE_ORGANISATEUR') !== false) {
                // Rediriger vers le calendrier pour les erreurs d'organisateur
                $response = new RedirectResponse($this->router->generate('calendar_index'));
                $event->setResponse($response);
                return;
            }
            
            if (strpos($message, 'ROLE_ADMIN') !== false) {
                // Rediriger vers la page d'accueil pour les erreurs d'admin
                $response = new RedirectResponse($this->router->generate('app_home'));
                $event->setResponse($response);
                return;
            }
            
            if (strpos($message, 'ROLE_PARTICIPANT') !== false) {
                // Rediriger vers la page d'accueil pour les erreurs de participant
                $response = new RedirectResponse($this->router->generate('app_home'));
                $event->setResponse($response);
                return;
            }
            
            // Gestion selon la route
            if (strpos($route, 'event') !== false) {
                $response = new RedirectResponse($this->router->generate('event_list'));
                $event->setResponse($response);
                return;
            }
            
            if (strpos($route, 'admin') !== false) {
                $response = new RedirectResponse($this->router->generate('app_home'));
                $event->setResponse($response);
                return;
            }
            
            if (strpos($route, 'salle') !== false || strpos($route, 'gestion-salle') !== false) {
                $response = new RedirectResponse($this->router->generate('calendar_index'));
                $event->setResponse($response);
                return;
            }
            
            // Redirection par défaut vers la page d'accueil
            $response = new RedirectResponse($this->router->generate('app_home'));
            $event->setResponse($response);
        }
    }
} 