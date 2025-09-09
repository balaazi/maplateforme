<?php

namespace App\Repository;

use App\Entity\Participation;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Participation>
 */
class ParticipationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Participation::class);
    }

    /**
     * Retourne les participations d'un utilisateur aux événements non archivés
     */
    public function findByUserNonArchived(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.event', 'e')
            ->where('p.user = :user')
            ->andWhere('e.archive = :archived')
            ->setParameter('user', $user)
            ->setParameter('archived', false)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les participations d'un utilisateur aux événements non annulés et non archivés
     */
    public function findByUserNonCancelledNonArchived(User $user): array
    {
        return $this->createQueryBuilder('p')
            ->join('p.event', 'e')
            ->where('p.user = :user')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->andWhere('e.archive = :archived')
            ->setParameter('user', $user)
            ->setParameter('cancelled', 'annulé')
            ->setParameter('archived', false)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les participations en attente qui ont dépassé la date d'expiration
     */
    public function findExpiredParticipations(\DateTime $expirationDate): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.invitationStatus = :status')
            ->andWhere('p.createdAt < :expirationDate')
            ->setParameter('status', 'pending')
            ->setParameter('expirationDate', $expirationDate)
            ->orderBy('p.createdAt', 'ASC')
            ->getQuery()
            ->getResult();
    }

    //    /**
    //     * @return Participation[] Returns an array of Participation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('p.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Participation
    //    {
    //        return $this->createQueryBuilder('p')
    //            ->andWhere('p.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
