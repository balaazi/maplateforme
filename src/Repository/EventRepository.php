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
            ->andWhere('e.archive = :archived')
            ->setParameter('start', $start)
            ->setParameter('end', $end)
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements filtrés selon le rôle de l'utilisateur
     * - ADMIN: voit tous les événements de la plateforme
     * - ORGANISATEUR: voit ses événements créés + ses participations
     * - PARTICIPANT: voit ses participations uniquement
     */
    public function findByRole(User $user): array
    {
        $roles = $user->getRoles();

        // Les administrateurs voient TOUS les événements de la plateforme
        if (in_array('ROLE_ADMIN', $roles)) {
            return $this->createQueryBuilder('e')
                ->where('e.status IS NULL OR e.status != :cancelled')
                ->andWhere('e.archive = :archived')
                ->setParameter('cancelled', 'annulé')
                ->setParameter('archived', false)
                ->orderBy('e.dateHeure', 'ASC')
                ->getQuery()
                ->getResult();
        }

        // Les organisateurs voient leurs événements créés + leurs participations
        if (in_array('ROLE_ORGANISATEUR', $roles)) {
            // Événements créés par l'organisateur
            $createdEvents = $this->createQueryBuilder('e')
                ->where('e.createdBy = :user')
                ->andWhere('e.status IS NULL OR e.status != :cancelled')
                ->andWhere('e.archive = :archived')
                ->setParameter('user', $user)
                ->setParameter('cancelled', 'annulé')
                ->setParameter('archived', false)
                ->getQuery()
                ->getResult();

            // Événements auxquels l'organisateur participe
            $participatingEvents = $this->findAcceptedEventsForParticipant($user);

            // Fusionner et dédupliquer
            $allEvents = array_merge($createdEvents, $participatingEvents);
            $uniqueEvents = [];
            $seenIds = [];

            foreach ($allEvents as $event) {
                if (!in_array($event->getId(), $seenIds)) {
                    $uniqueEvents[] = $event;
                    $seenIds[] = $event->getId();
                }
            }

            // Trier par date
            usort($uniqueEvents, function($a, $b) {
                return $a->getDateHeure() <=> $b->getDateHeure();
            });

            return $uniqueEvents;
        }

        // Les participants ne voient que leurs événements acceptés
        return $this->findAcceptedEventsForParticipant($user);
    }

    /**
     * Retourne les événements actifs pour un utilisateur (exclut les annulés et archivés)
     * Note: Cette méthode est utilisée pour la gestion des événements, pas pour l'affichage agenda
     */
    public function findEventsForUser(User $user): array
    {
        // Tous les utilisateurs (y compris l'admin) ne voient que leurs propres événements créés
        return $this->createQueryBuilder('e')
            ->where('e.createdBy = :user')
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
     * Retourne les événements acceptés par un participant
     * Un participant ne voit que les événements qu'il a acceptés via une invitation
     */
    public function findAcceptedEventsForParticipant(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->join('e.invitations', 'i')
            ->where('i.email = :userEmail')
            ->andWhere('i.status = :acceptedStatus')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->andWhere('e.archive = :archived')
            ->setParameter('userEmail', $user->getEmail())
            ->setParameter('acceptedStatus', 'accepted')
            ->setParameter('cancelled', 'annulé')
            ->setParameter('archived', false)
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements archivés pour un utilisateur
     */
    public function findArchivedEventsForUser(User $user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('e.archive = :archived')
            ->setParameter('user', $user)
            ->setParameter('archived', true)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Vérifie s'il y a un conflit d'horaires pour un utilisateur avec un nouvel événement
     * Retourne l'événement en conflit s'il y en a un, null sinon
     */
    public function findConflictingEventForUser(User $user, Event $newEvent): ?Event
    {
        $newEventStart = $newEvent->getDateHeure();
        $newEventEnd = (clone $newEventStart)->modify('+' . $newEvent->getDuree() . ' minutes');

        // Trouver tous les événements que l'utilisateur a acceptés via les invitations
        $acceptedEventsViaInvitations = $this->createQueryBuilder('e')
            ->join('e.invitations', 'i')
            ->where('i.email = :userEmail')
            ->andWhere('i.status = :acceptedStatus')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->andWhere('e.archive = :archived')
            ->andWhere('e.id != :newEventId') // Exclure l'événement lui-même
            ->setParameter('userEmail', $user->getEmail())
            ->setParameter('acceptedStatus', 'accepted')
            ->setParameter('cancelled', 'annulé')
            ->setParameter('archived', false)
            ->setParameter('newEventId', $newEvent->getId())
            ->getQuery()
            ->getResult();

        // Trouver tous les événements où l'utilisateur participe directement
        $participatingEvents = $this->createQueryBuilder('e')
            ->join('e.participations', 'p')
            ->where('p.user = :user')
            ->andWhere('p.invitationStatus = :acceptedStatus')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->andWhere('e.archive = :archived')
            ->andWhere('e.id != :newEventId') // Exclure l'événement lui-même
            ->setParameter('user', $user)
            ->setParameter('acceptedStatus', 'accepté')
            ->setParameter('cancelled', 'annulé')
            ->setParameter('archived', false)
            ->setParameter('newEventId', $newEvent->getId())
            ->getQuery()
            ->getResult();

        // Combiner les deux listes et supprimer les doublons
        $allAcceptedEvents = array_merge($acceptedEventsViaInvitations, $participatingEvents);
        $allAcceptedEvents = array_unique($allAcceptedEvents, SORT_REGULAR);

        // Vérifier les chevauchements d'horaires
        foreach ($allAcceptedEvents as $existingEvent) {
            $existingStart = $existingEvent->getDateHeure();
            $existingEnd = (clone $existingStart)->modify('+' . $existingEvent->getDuree() . ' minutes');

            // Vérifier s'il y a chevauchement (même minute)
            if ($newEventStart < $existingEnd && $newEventEnd > $existingStart) {
                return $existingEvent; // Conflit détecté
            }
        }

        return null; // Aucun conflit
    }

    /**
     * Retourne les événements dont la date est dépassée depuis plus d'un jour
     */
    public function findExpiredEvents(int $daysAgo = 1): array
    {
        $limitDate = (new \DateTime())->modify('-' . $daysAgo . ' days')->setTime(0, 0, 0);
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure < :limitDate')
            ->andWhere('e.archive = :archived')
            ->setParameter('limitDate', $limitDate)
            ->setParameter('archived', false)
            ->getQuery()
            ->getResult();
    }

    /**
     * Retourne les événements non archivés dont la date est dépassée depuis plus d'un jour
     */
    public function findNonArchivedExpiredEvents(int $daysAgo = 1): array
    {
        $limitDate = (new \DateTime())->modify('-' . $daysAgo . ' days')->setTime(0, 0, 0);
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure < :limitDate')
            ->andWhere('e.archive = :archived')
            ->setParameter('limitDate', $limitDate)
            ->setParameter('archived', false)
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
