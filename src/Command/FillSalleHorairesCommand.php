<?php

namespace App\Command;

use App\Entity\Salle;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand(
    name: 'app:fill-salle-horaires',
    description: 'Remplit automatiquement le champ horairesParJour de toutes les salles avec un horaire par défaut.'
)]
class FillSalleHorairesCommand extends Command
{
    private EntityManagerInterface $em;

    public function __construct(EntityManagerInterface $em)
    {
        parent::__construct();
        $this->em = $em;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $repo = $this->em->getRepository(Salle::class);
        $salles = $repo->findAll();
        $default = [
            'monday' =>    ['debut' => '08:00', 'fin' => '18:00'],
            'tuesday' =>   ['debut' => '08:00', 'fin' => '18:00'],
            'wednesday' => ['debut' => '08:00', 'fin' => '18:00'],
            'thursday' =>  ['debut' => '08:00', 'fin' => '18:00'],
            'friday' =>    ['debut' => '08:00', 'fin' => '18:00'],
            'saturday' =>  ['debut' => '10:00', 'fin' => '14:00'],
            'sunday' =>    null
        ];
        $count = 0;
        foreach ($salles as $salle) {
            $salle->setHorairesParJour($default);
            $count++;
        }
        $this->em->flush();
        $output->writeln("<info>$count salles mises à jour avec horairesParJour par défaut.</info>");
        return Command::SUCCESS;
    }
} 