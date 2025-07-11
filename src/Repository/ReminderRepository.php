<?php

namespace App\Repository;

use App\Entity\Reminder;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reminder>
 */
class ReminderRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reminder::class);
    }

    /**
     * Trouve tous les rappels qui doivent être déclenchés maintenant
     */
    public function findPendingReminders(): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.isDone = :done')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.dueDate <= :now')
            ->setParameter('done', false)
            ->setParameter('triggered', false)
            ->setParameter('now', new \DateTime())
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rappels pour un utilisateur spécifique
     */
    public function findRemindersByUser(User $user, bool $onlyActive = true): array
    {
        $qb = $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->setParameter('user', $user)
            ->orderBy('r.dueDate', 'DESC');

        if ($onlyActive) {
            $qb->andWhere('r.isDone = :done')
               ->setParameter('done', false);
        }

        return $qb->getQuery()->getResult();
    }

    /**
     * Trouve les rappels pour un événement spécifique
     */
    public function findRemindersByEvent($event): array
    {
        return $this->createQueryBuilder('r')
            ->where('r.event = :event')
            ->setParameter('event', $event)
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les rappels à déclencher dans les X prochaines minutes
     */
    public function findUpcomingReminders(int $minutes = 5): array
    {
        $now = new \DateTime();
        $future = (clone $now)->modify("+{$minutes} minutes");

        return $this->createQueryBuilder('r')
            ->where('r.isDone = :done')
            ->andWhere('r.isTriggered = :triggered')
            ->andWhere('r.dueDate BETWEEN :now AND :future')
            ->setParameter('done', false)
            ->setParameter('triggered', false)
            ->setParameter('now', $now)
            ->setParameter('future', $future)
            ->orderBy('r.dueDate', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Compte les rappels actifs pour un utilisateur
     */
    public function countActiveRemindersByUser(User $user): int
    {
        return $this->createQueryBuilder('r')
            ->select('COUNT(r.id)')
            ->where('r.user = :user')
            ->andWhere('r.isDone = :done')
            ->andWhere('r.isTriggered = :triggered')
            ->setParameter('user', $user)
            ->setParameter('done', false)
            ->setParameter('triggered', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * Supprime les anciens rappels déclenchés
     */
    public function cleanupOldReminders(int $daysOld = 30): int
    {
        $cutoffDate = (new \DateTime())->modify("-{$daysOld} days");

        return $this->createQueryBuilder('r')
            ->delete()
            ->where('r.isDone = :done OR r.isTriggered = :triggered')
            ->andWhere('r.triggeredAt < :cutoff OR r.createdAt < :cutoff')
            ->setParameter('done', true)
            ->setParameter('triggered', true)
            ->setParameter('cutoff', $cutoffDate)
            ->getQuery()
            ->execute();
    }

    /**
     * Trouve les rappels en conflit pour éviter les doublons
     */
    public function findConflictingReminders(User $user, $event, \DateTimeInterface $dueDate, int $toleranceMinutes = 15): array
    {
        $start = (new \DateTime($dueDate->format('Y-m-d H:i:s')))->modify("-{$toleranceMinutes} minutes");
        $end = (new \DateTime($dueDate->format('Y-m-d H:i:s')))->modify("+{$toleranceMinutes} minutes");

        return $this->createQueryBuilder('r')
            ->where('r.user = :user')
            ->andWhere('r.event = :event')
            ->andWhere('r.dueDate BETWEEN :start AND :end')
            ->andWhere('r.isDone = :done')
            ->setParameter('user', $user)
            ->setParameter('event', $event)
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('done', false)
            ->getQuery()
            ->getResult();
    }
} 