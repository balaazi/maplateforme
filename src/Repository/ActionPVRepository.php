<?php

namespace App\Repository;

use App\Entity\ActionPV;
use App\Entity\ProcesVerbal;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ActionPV>
 */
class ActionPVRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ActionPV::class);
    }

    /**
     * Trouve les actions d'un PV
     */
    public function findByProcesVerbal(ProcesVerbal $pv): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.procesVerbal = :pv')
            ->setParameter('pv', $pv)
            ->orderBy('a.delai', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les actions assignées à un utilisateur
     */
    public function findByResponsable(User $responsable): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.responsable = :responsable')
            ->setParameter('responsable', $responsable)
            ->orderBy('a.delai', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les actions en retard
     */
    public function findActionsEnRetard(): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.delai < :today')
            ->andWhere('a.statut != :termine')
            ->setParameter('today', new \DateTime())
            ->setParameter('termine', 'termine')
            ->orderBy('a.delai', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les actions par statut
     */
    public function findByStatut(string $statut): array
    {
        return $this->createQueryBuilder('a')
            ->andWhere('a.statut = :statut')
            ->setParameter('statut', $statut)
            ->orderBy('a.delai', 'ASC')
            ->getQuery()
            ->getResult();
    }
}