<?php

namespace App\Repository;

use App\Entity\ProcesVerbal;
use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProcesVerbal>
 */
class ProcesVerbalRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProcesVerbal::class);
    }

    /**
     * Trouve le PV associé à un événement
     */
    public function findByEvent(Event $event): ?ProcesVerbal
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.event = :event')
            ->setParameter('event', $event)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve tous les PV rédigés par un utilisateur
     */
    public function findByRedacteur(User $redacteur): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.redacteur = :redacteur')
            ->setParameter('redacteur', $redacteur)
            ->orderBy('pv.dateCreation', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les PV finalisés
     */
    public function findFinalises(): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.finalise = :finalise')
            ->setParameter('finalise', true)
            ->orderBy('pv.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les PV en cours de rédaction
     */
    public function findEnCours(): array
    {
        return $this->createQueryBuilder('pv')
            ->andWhere('pv.finalise = :finalise')
            ->setParameter('finalise', false)
            ->orderBy('pv.dateModification', 'DESC')
            ->getQuery()
            ->getResult();
    }
}