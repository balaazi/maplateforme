<?php

namespace App\Repository;

use App\Entity\Event;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    /**
     * Retourne les événements qui commencent à une heure précise (ex: dans 1h).
     */
    public function findByDateRange(\DateTimeInterface $start, \DateTimeInterface $end): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure >= :start')
            ->andWhere('e.dateHeure < :end')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements filtrés selon le rôle de l'utilisateur
     */
    public function findByRole(User $user): array
    {
        $roles = $user->getRoles();

        // Admin peut voir tous les événements (sauf annulés)
        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->createQueryBuilder('e')
                ->where('e.status IS NULL OR e.status != :cancelled')
                ->setParameter('cancelled', 'annulé')
                ->orderBy('e.dateHeure', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // Organisateur peut voir ses événements créés + ceux auxquels il participe (sauf annulés)
        if (in_array('ROLE_ORGANISATEUR', $roles)) {
            return $this->createQueryBuilder('e')
                ->leftJoin('e.participations', 'p')
                ->where('e.organizer = :user OR p.user = :user')
                ->andWhere('e.status IS NULL OR e.status != :cancelled')
                ->setParameter('user', $user)
                ->setParameter('cancelled', 'annulé')
                ->orderBy('e.dateHeure', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // Participant peut voir les événements auxquels il participe OU qu'il organise (sauf annulés)
        return $this->createQueryBuilder('e')
            ->leftJoin('e.participations', 'p')
            ->where('e.organizer = :user OR p.user = :user')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->setParameter('user', $user)
            ->setParameter('cancelled', 'annulé')
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    // Autres méthodes générées (commentées)
    /*
    public function findByExampleField($value): array
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->orderBy('e.id', 'ASC')
            ->setMaxResults(10)
            ->getQuery()
            ->getResult()
        ;
    }

    public function findOneBySomeField($value): ?Event
    {
        return $this->createQueryBuilder('e')
            ->andWhere('e.exampleField = :val')
            ->setParameter('val', $value)
            ->getQuery()
            ->getOneOrNullResult()
        ;
    }
    */
}
