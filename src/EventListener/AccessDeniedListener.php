<?php

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\KernelEvents;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\Response;

class AccessDeniedListener implements EventSubscriberInterface
{
    private LoggerInterface $logger;

    public function __construct(LoggerInterface $logger)
    {
        $this->logger = $logger;
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
            
            // Logger les détails de l'erreur d'accès
            $this->logger->error('Access Denied Error Details', [
                'route' => $request->attributes->get('_route'),
                'controller' => $request->attributes->get('_controller'),
                'uri' => $request->getRequestUri(),
                'method' => $request->getMethod(),
                'ip' => $request->getClientIp(),
                'user_agent' => $request->headers->get('User-Agent'),
                'referer' => $request->headers->get('referer'),
                'is_ajax' => $request->isXmlHttpRequest(),
                'message' => $exception->getMessage(),
                'trace' => $exception->getTraceAsString()
            ]);

            // Si c'est une erreur ROLE_ORGANISATEUR, proposer une redirection
            if (strpos($exception->getMessage(), 'ROLE_ORGANISATEUR') !== false) {
                // Rediriger vers le calendrier au lieu de montrer l'erreur
                $response = new Response();
                $response->headers->set('Location', '/calendar');
                $response->setStatusCode(302);
                $event->setResponse($response);
            }
        }
    }
} 