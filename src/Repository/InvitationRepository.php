<?php

namespace App\Repository;

use App\Entity\Invitation;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invitation>
 */
class InvitationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Invitation::class);
    }

    /**
     * Trouve les invitations en attente qui ont dépassé la date d'expiration
     */
    public function findExpiredInvitations(\DateTime $expirationDate): array
    {
        $qb = $this->createQueryBuilder('i')
            ->select('i', 'e')  // Sélectionner aussi l'événement
            ->leftJoin('i.event', 'e')  // Joindre la table des événements
            ->andWhere('i.status = :status')
            ->andWhere('i.createdAt < :expirationDate')
            ->setParameter('status', 'pending')
            ->setParameter('expirationDate', $expirationDate)
            ->orderBy('i.createdAt', 'ASC');

        $result = $qb->getQuery()->getResult();

        // Logger les résultats pour le débogage
        foreach ($result as $invitation) {
            $event = $invitation->getEvent();
            error_log(sprintf(
                "Invitation trouvée - ID: %d, Email: %s, Type d'événement: %s, Créée le: %s",
                $invitation->getId(),
                $invitation->getEmail(),
                $event ? $event->getType() : 'inconnu',
                $invitation->getCreatedAt()->format('Y-m-d H:i:s')
            ));
        }

        return $result;
    }

    /**
     * Trouve la participation associée à une invitation
     */
    public function findParticipationForInvitation(Invitation $invitation): ?object
    {
        $event = $invitation->getEvent();
        $email = $invitation->getEmail();
        
        if (!$event) {
            return null;
        }

        // Chercher l'utilisateur par email
        $user = $this->getEntityManager()
            ->getRepository('App\Entity\User')
            ->findOneBy(['email' => $email]);
        
        if (!$user) {
            return null;
        }

        // Chercher la participation de cet utilisateur pour cet événement
        return $this->getEntityManager()
            ->getRepository('App\Entity\Participation')
            ->findOneBy([
                'user' => $user,
                'event' => $event
            ]);
    }

    //    /**
    //     * @return Invitation[] Returns an array of Invitation objects
    //     */
    //    public function findByExampleField($value): array
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->orderBy('i.id', 'ASC')
    //            ->setMaxResults(10)
    //            ->getQuery()
    //            ->getResult()
    //        ;
    //    }

    //    public function findOneBySomeField($value): ?Invitation
    //    {
    //        return $this->createQueryBuilder('i')
    //            ->andWhere('i.exampleField = :val')
    //            ->setParameter('val', $value)
    //            ->getQuery()
    //            ->getOneOrNullResult()
    //        ;
    //    }
}
