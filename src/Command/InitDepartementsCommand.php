<?php

namespace App\Command;

use App\Entity\Departement;
use App\Repository\DepartementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:init-departements',
    description: 'Initialise la liste des départements dans la base de données',
)]
class InitDepartementsCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private DepartementRepository $departementRepository
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('force', 'f', InputOption::VALUE_NONE, 'Force l\'insertion même si des départements existent déjà')
            ->addOption('clear', null, InputOption::VALUE_NONE, 'Supprime tous les départements existants avant l\'insertion')
            ->setHelp('Cette commande initialise la base de données avec une liste prédéfinie de départements...');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Liste des départements à insérer
        $departementsData = $this->getDefaultDepartements();

        // Vérification si des départements existent déjà
        $existingCount = $this->departementRepository->count([]);
        
        if ($existingCount > 0 && !$input->getOption('force') && !$input->getOption('clear')) {
            $io->warning(sprintf('Il y a déjà %d département(s) dans la base de données.', $existingCount));
            $io->note('Utilisez --force pour ajouter les départements manquants ou --clear pour tout supprimer et réinsérer.');
            return Command::SUCCESS;
        }

        // Option clear : suppression de tous les départements existants
        if ($input->getOption('clear')) {
            $io->warning('Suppression de tous les départements existants...');
            
            if (!$io->confirm('Êtes-vous sûr de vouloir supprimer tous les départements existants ?', false)) {
                $io->info('Opération annulée.');
                return Command::SUCCESS;
            }

            $this->clearAllDepartements();
            $io->success('Tous les départements ont été supprimés.');
        }

        $io->title('Initialisation des départements');

        $inserted = 0;
        $skipped = 0;
        $errors = 0;

        foreach ($departementsData as $data) {
            try {
                // Vérification si le département existe déjà (par code ou nom)
                $existing = $this->departementRepository->findOneBy([
                    'code' => $data['code']
                ]);

                if ($existing && !$input->getOption('clear')) {
                    $io->text(sprintf('Département "%s" (%s) existe déjà - ignoré', $data['nom'], $data['code']));
                    $skipped++;
                    continue;
                }

                // Création du nouveau département
                $departement = new Departement();
                $departement->setNom($data['nom']);
                $departement->setCode($data['code']);
                $departement->setDescription($data['description'] ?? null);
                $departement->setResponsable($data['responsable'] ?? null);
                $departement->setEmailContact($data['emailContact'] ?? null);
                $departement->setTelephone($data['telephone'] ?? null);
                $departement->setLocalisation($data['localisation'] ?? null);
                $departement->setBudgetAnnuel($data['budgetAnnuel'] ?? 0);
                $departement->setActif($data['actif'] ?? true);

                $this->entityManager->persist($departement);
                
                $io->text(sprintf('✓ Département "%s" (%s) ajouté', $data['nom'], $data['code']));
                $inserted++;

            } catch (\Exception $e) {
                $io->error(sprintf('Erreur lors de l\'insertion du département "%s": %s', $data['nom'] ?? 'Inconnu', $e->getMessage()));
                $errors++;
            }
        }

        try {
            $this->entityManager->flush();
            
            $io->newLine();
            $io->success(sprintf(
                'Initialisation terminée ! %d département(s) ajouté(s), %d ignoré(s), %d erreur(s)',
                $inserted,
                $skipped,
                $errors
            ));

            if ($inserted > 0) {
                $io->table(
                    ['Code', 'Nom', 'Responsable', 'Localisation'],
                    array_map(function($data) {
                        return [
                            $data['code'],
                            $data['nom'],
                            $data['responsable'] ?? 'Non défini',
                            $data['localisation'] ?? 'Non définie'
                        ];
                    }, array_slice($departementsData, 0, $inserted))
                );
            }

            return Command::SUCCESS;

        } catch (\Exception $e) {
            $io->error('Erreur lors de la sauvegarde : ' . $e->getMessage());
            return Command::FAILURE;
        }
    }

    private function clearAllDepartements(): void
    {
        $qb = $this->entityManager->createQueryBuilder();
        $qb->delete(Departement::class, 'd');
        $qb->getQuery()->execute();
    }

    private function getDefaultDepartements(): array
    {
        return [
            [
                'nom' => 'Direction Générale',
                'code' => 'DG',
                'description' => 'Direction générale de l\'entreprise',
                'responsable' => 'Directeur Général',
                'emailContact' => 'direction@entreprise.com',
                'telephone' => '01.23.45.67.89',
                'localisation' => 'Bâtiment A - Étage 3',
                'budgetAnnuel' => 500000,
                'actif' => true
            ],
            [
                'nom' => 'Ressources Humaines',
                'code' => 'RH',
                'description' => 'Gestion des ressources humaines et du personnel',
                'responsable' => 'Directeur RH',
                'emailContact' => 'rh@entreprise.com',
                'telephone' => '01.23.45.67.90',
                'localisation' => 'Bâtiment A - Étage 2',
                'budgetAnnuel' => 200000,
                'actif' => true
            ],
            [
                'nom' => 'Informatique',
                'code' => 'IT',
                'description' => 'Département informatique et systèmes d\'information',
                'responsable' => 'DSI',
                'emailContact' => 'it@entreprise.com',
                'telephone' => '01.23.45.67.91',
                'localisation' => 'Bâtiment B - Étage 1',
                'budgetAnnuel' => 300000,
                'actif' => true
            ],
            [
                'nom' => 'Comptabilité',
                'code' => 'COMPTA',
                'description' => 'Service comptabilité et finances',
                'responsable' => 'Chef Comptable',
                'emailContact' => 'compta@entreprise.com',
                'telephone' => '01.23.45.67.92',
                'localisation' => 'Bâtiment A - Étage 1',
                'budgetAnnuel' => 150000,
                'actif' => true
            ],
            [
                'nom' => 'Commercial',
                'code' => 'COM',
                'description' => 'Équipe commerciale et ventes',
                'responsable' => 'Directeur Commercial',
                'emailContact' => 'commercial@entreprise.com',
                'telephone' => '01.23.45.67.93',
                'localisation' => 'Bâtiment C - Étage 2',
                'budgetAnnuel' => 400000,
                'actif' => true
            ],
            [
                'nom' => 'Marketing',
                'code' => 'MKT',
                'description' => 'Service marketing et communication',
                'responsable' => 'Responsable Marketing',
                'emailContact' => 'marketing@entreprise.com',
                'telephone' => '01.23.45.67.94',
                'localisation' => 'Bâtiment C - Étage 1',
                'budgetAnnuel' => 250000,
                'actif' => true
            ]
        ];
    }
}