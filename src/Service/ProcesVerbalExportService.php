<?php

namespace App\Service;

use App\Entity\ProcesVerbal;
use Twig\Environment;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\ResponseHeaderBag;

class ProcesVerbalExportService
{
    private Environment $twig;

    public function __construct(Environment $twig)
    {
        $this->twig = $twig;
    }

    /**
     * Exporte le procès-verbal en HTML pour aperçu ou impression
     */
    public function exportToHtml(ProcesVerbal $procesVerbal): string
    {
        return $this->twig->render('proces_verbal/export/html.html.twig', [
            'procesVerbal' => $procesVerbal,
            'event' => $procesVerbal->getEvent(),
            'dateExport' => new \DateTime()
        ]);
    }

    /**
     * Génère une réponse HTTP pour l'export PDF
     * Note: Nécessitera l'installation de TCPDF ou DomPDF
     */
    public function exportToPdf(ProcesVerbal $procesVerbal): Response
    {
        // Pour l'instant, on génère un HTML formaté pour l'impression
        // TODO: Implémenter avec TCPDF ou DomPDF
        $html = $this->exportToHtml($procesVerbal);
        
        $response = new Response($html);
        $response->headers->set('Content-Type', 'text/html');
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="PV_' . $procesVerbal->getEvent()->getTitle() . '_' . $procesVerbal->getDateHeure()->format('Y-m-d') . '.html"');
        
        return $response;
    }

    /**
     * Génère une réponse HTTP pour l'export Word
     * Note: Nécessitera l'installation de PhpWord
     */
    public function exportToWord(ProcesVerbal $procesVerbal): Response
    {
        // Génération d'un document RTF simple qui peut être ouvert par Word
        $content = $this->generateRtfContent($procesVerbal);
        
        $response = new Response($content);
        $response->headers->set('Content-Type', 'application/rtf');
        $response->headers->set('Content-Disposition', ResponseHeaderBag::DISPOSITION_ATTACHMENT . '; filename="PV_' . $procesVerbal->getEvent()->getTitle() . '_' . $procesVerbal->getDateHeure()->format('Y-m-d') . '.rtf"');
        
        return $response;
    }

    /**
     * Génère le contenu RTF pour export Word
     */
    private function generateRtfContent(ProcesVerbal $procesVerbal): string
    {
        $event = $procesVerbal->getEvent();
        
        $rtf = '{\\rtf1\\ansi\\deff0 {\\fonttbl {\\f0 Times New Roman;}}';
        $rtf .= '\\f0\\fs24 ';
        
        // En-tête
        $rtf .= '{\\b\\fs32 PROCÈS-VERBAL DE RÉUNION}\\par\\par';
        
        // Informations sur l'événement
        $rtf .= '{\\b Événement :} ' . $this->escapeRtf($event->getTitle()) . '\\par';
        $rtf .= '{\\b Date et heure :} ' . $procesVerbal->getDateHeure()->format('d/m/Y à H:i') . '\\par';
        $rtf .= '{\\b Durée :} ' . $event->getDuree() . ' minutes\\par';
        
        if ($event->getSalle()) {
            $rtf .= '{\\b Lieu :} ' . $this->escapeRtf($event->getSalle()->getNom()) . '\\par';
        }
        
        $rtf .= '{\\b Organisateur :} ' . $this->escapeRtf($event->getOrganizer()->getPrenom() . ' ' . $event->getOrganizer()->getNom()) . '\\par';
        $rtf .= '{\\b Rédacteur :} ' . $this->escapeRtf($procesVerbal->getRedacteur()->getPrenom() . ' ' . $procesVerbal->getRedacteur()->getNom()) . '\\par\\par';
        
        // Participants
        $rtf .= '{\\b\\fs28 PARTICIPANTS}\\par\\par';
        $rtf .= $this->escapeRtf($procesVerbal->getParticipants()) . '\\par\\par';
        
        // Points abordés
        $rtf .= '{\\b\\fs28 POINTS ABORDÉS}\\par\\par';
        $rtf .= $this->escapeRtf($procesVerbal->getPointsAbordes()) . '\\par\\par';
        
        // Décisions prises
        $rtf .= '{\\b\\fs28 DÉCISIONS PRISES}\\par\\par';
        $rtf .= $this->escapeRtf($procesVerbal->getDecisionsPrises()) . '\\par\\par';
        
        // Actions à réaliser
        if ($procesVerbal->getActions()->count() > 0) {
            $rtf .= '{\\b\\fs28 ACTIONS À RÉALISER}\\par\\par';
            
            foreach ($procesVerbal->getActions() as $index => $action) {
                $rtf .= '{\\b ' . ($index + 1) . '. } ' . $this->escapeRtf($action->getDescription()) . '\\par';
                
                $responsable = '';
                if ($action->getResponsable()) {
                    $responsable = $action->getResponsable()->getPrenom() . ' ' . $action->getResponsable()->getNom();
                } elseif ($action->getResponsableNom()) {
                    $responsable = $action->getResponsableNom();
                }
                
                if ($responsable) {
                    $rtf .= '{\\i Responsable :} ' . $this->escapeRtf($responsable) . '\\par';
                }
                
                if ($action->getDelai()) {
                    $rtf .= '{\\i Délai :} ' . $action->getDelai()->format('d/m/Y') . '\\par';
                }
                
                $rtf .= '{\\i Statut :} ' . $this->escapeRtf(ucfirst(str_replace('_', ' ', $action->getStatut()))) . '\\par';
                
                if ($action->getNotes()) {
                    $rtf .= '{\\i Notes :} ' . $this->escapeRtf($action->getNotes()) . '\\par';
                }
                
                $rtf .= '\\par';
            }
        }
        
        // Pied de page
        $rtf .= '\\par{\\i Document généré le ' . (new \DateTime())->format('d/m/Y à H:i') . '}\\par';
        
        $rtf .= '}';
        
        return $rtf;
    }

    /**
     * Échappe les caractères spéciaux pour RTF
     */
    private function escapeRtf(string $text): string
    {
        $text = str_replace('\\', '\\\\', $text);
        $text = str_replace('{', '\\{', $text);
        $text = str_replace('}', '\\}', $text);
        $text = str_replace("\n", '\\par ', $text);
        
        return $text;
    }

    /**
     * Génère les données pour le partage par email
     */
    public function generateEmailData(ProcesVerbal $procesVerbal): array
    {
        $event = $procesVerbal->getEvent();
        
        return [
            'subject' => 'Procès-verbal - ' . $event->getTitle(),
            'event' => $event,
            'procesVerbal' => $procesVerbal,
            'attachmentName' => 'PV_' . $event->getTitle() . '_' . $procesVerbal->getDateHeure()->format('Y-m-d'),
        ];
    }
}