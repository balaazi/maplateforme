<?php

namespace App\EventListener;

use App\Service\AutoArchiveService;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\KernelEvents;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Psr\Log\LoggerInterface;

class AutoArchiveListener implements EventSubscriberInterface
{
    private const ARCHIVE_ROUTES = [
        'event_list',
        'event_show',
        'participant_events',
        'participant_documents',
        'participant_statistics',
        'calendar_index',
        'organisateur_dashboard',
        'participant_dashboard'
    ];

    public function __construct(
        private AutoArchiveService $autoArchiveService,
        private LoggerInterface $logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => 'onKernelRequest',
        ];
    }

    public function onKernelRequest(RequestEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $request = $event->getRequest();
        $route = $request->get('_route');

        // Vérifier si la route actuelle nécessite un archivage automatique
        if ($route && in_array($route, self::ARCHIVE_ROUTES)) {
            try {
                $archivedCount = $this->autoArchiveService->checkAndArchiveCompletedEvents();
                
                if ($archivedCount > 0) {
                    $this->logger->info("Archivage automatique déclenché par la route '{$route}' : {$archivedCount} événement(s) archivé(s)");
                }
            } catch (\Exception $e) {
                $this->logger->error('Erreur lors de l\'archivage automatique', [
                    'route' => $route,
                    'error' => $e->getMessage()
                ]);
            }
        }
    }
} 