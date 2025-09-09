<?php

namespace App\Command;

use App\Service\InvitationStatusDiagnosticService;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:diagnose-invitation-status',
    description: 'Diagnostique et corrige les problèmes de statuts d\'invitation'
)]
class DiagnoseInvitationStatusCommand extends Command
{
    public function __construct(
        private InvitationStatusDiagnosticService $diagnosticService
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption(
                'fix',
                'f',
                InputOption::VALUE_NONE,
                'Corriger automatiquement les problèmes détectés'
            )
            ->addOption(
                'details',
                'd',
                InputOption::VALUE_NONE,
                'Afficher les détails complets'
            )
            ->addOption(
                'force',
                null,
                InputOption::VALUE_NONE,
                'Forcer la correction sans confirmation'
            );
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $fix = $input->getOption('fix');
        $details = $input->getOption('details');

        $io->title('🔍 Diagnostic des Statuts d\'Invitation - EventHub');

        // Générer le rapport de diagnostic
        $io->section('Analyse des problèmes...');
        $report = $this->diagnosticService->generateDiagnosticReport();

        // Afficher le résumé
        $io->section('📊 Résumé du Diagnostic');
        $io->table(
            ['Métrique', 'Valeur'],
            [
                ['Problèmes totaux', $report['summary']['total_issues']],
                ['Types de problèmes', implode(', ', $report['summary']['issue_types'] ?: ['Aucun'])],
                ['Timestamp', $report['summary']['timestamp']]
            ]
        );

        // Afficher les problèmes détectés
        if (!empty($report['issues'])) {
            $io->section('⚠️ Problèmes Détectés');
            
            foreach ($report['issues'] as $type => $issue) {
                $io->text("<comment>{$type}: {$issue['count']} problème(s)</comment>");
                
                if ($details && !empty($issue['details'])) {
                    $io->table(
                        ['ID', 'Email/Utilisateur', 'Statut Invalide', 'Événement'],
                        array_map(function($detail) {
                            return [
                                $detail['id'] ?? 'N/A',
                                $detail['email'] ?? $detail['user_email'] ?? 'N/A',
                                $detail['invalid_status'] ?? 'N/A',
                                $detail['event_title'] ?? 'N/A'
                            ];
                        }, $issue['details'])
                    );
                }
            }
        } else {
            $io->success('✅ Aucun problème détecté - les statuts sont cohérents !');
        }

        // Afficher les recommandations
        $io->section('💡 Recommandations');
        foreach ($report['recommendations'] as $recommendation) {
            $io->text("• {$recommendation}");
        }

        // Corriger automatiquement si demandé
        if ($fix && !empty($report['issues'])) {
            $io->section('🔧 Correction Automatique');
            
            if ($input->getOption('force') || $io->confirm('Voulez-vous corriger automatiquement ces problèmes ?', false)) {
                $io->text('Application des corrections...');
                
                try {
                    $fixed = $this->diagnosticService->fixStatusInconsistencies();
                    
                    if (!empty($fixed)) {
                        $io->success('✅ Corrections appliquées avec succès !');
                        
                        if ($details) {
                            $io->table(
                                ['Type de Correction', 'Nombre'],
                                array_map(function($type, $items) {
                                    return [$type, count($items)];
                                }, array_keys($fixed), $fixed)
                            );
                        }
                    } else {
                        $io->info('ℹ️ Aucune correction nécessaire');
                    }
                } catch (\Exception $e) {
                    $io->error("❌ Erreur lors de la correction: {$e->getMessage()}");
                    return Command::FAILURE;
                }
            } else {
                $io->info('ℹ️ Correction annulée par l\'utilisateur');
            }
        } elseif ($fix && empty($report['issues'])) {
            $io->info('ℹ️ Aucune correction nécessaire - aucun problème détecté');
        }

        $io->newLine();
        $io->success('Diagnostic terminé !');

        return Command::SUCCESS;
    }
}
