<?php

namespace App\Repository;

use App\Entity\Event;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Event>
 *
 * @method Event|null find($id, $lockMode = null, $lockVersion = null)
 * @method Event|null findOneBy(array $criteria, array $orderBy = null)
 * @method Event[]    findAll()
 * @method Event[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class EventRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Event::class);
    }

    public function save(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->persist($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    public function remove(Event $entity, bool $flush = false): void
    {
        $this->getEntityManager()->remove($entity);

        if ($flush) {
            $this->getEntityManager()->flush();
        }
    }

    /**
     * Trouve les événements qui se sont terminés entre deux dates
     */
    public function findEventsEndedBetween(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        $qb = $this->createQueryBuilder('e');
        
        return $qb->where('e.dateHeure < :toDate')
            ->andWhere('e.dateHeure > :fromDate')
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les événements à venir
     */
    public function findUpcomingEvents(): array
    {
        $now = new \DateTime();
        
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure > :now')
            ->setParameter('now', $now)
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les événements dans une plage de dates spécifique
     */
    public function findByDateRange(\DateTimeInterface $fromDate, \DateTimeInterface $toDate): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure >= :fromDate')
            ->andWhere('e.dateHeure < :toDate')
            ->setParameter('fromDate', $fromDate)
            ->setParameter('toDate', $toDate)
            ->orderBy('e.dateHeure', 'ASC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les événements actifs (non archivés) pour un utilisateur spécifique
     */
    public function findEventsForUser($user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('e.archive = :archive')
            ->setParameter('user', $user)
            ->setParameter('archive', false)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve tous les événements pour l'affichage dans le calendrier selon le rôle de l'utilisateur
     * Ne montre que les événements qui ne sont pas passés depuis plus d'un jour
     */
    public function findByRole($user): array
    {
        // Vérifier si l'utilisateur a le rôle ROLE_ADMIN dans ses rôles
        $roles = $user->getRoles();
        $isAdmin = in_array('ROLE_ADMIN', $roles);
        
        // Date limite : aujourd'hui moins 1 jour
        $cutoffDate = new \DateTime('-1 day');
        
        $qb = $this->createQueryBuilder('e');
        
        // Si c'est un admin, on récupère tous les événements
        if ($isAdmin) {
            return $qb->where('e.dateHeure >= :cutoffDate')
                ->setParameter('cutoffDate', $cutoffDate)
                ->orderBy('e.dateHeure', 'DESC')
                ->getQuery()
                ->getResult();
        }
        
        // Pour les organisateurs, on ne récupère que les événements qu'ils ont créés
        // et qui ne sont pas passés depuis plus d'un jour
        return $qb->where('e.organizer = :user')
            ->andWhere('e.dateHeure >= :cutoffDate')
            ->setParameter('user', $user)
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve tous les événements pour les administrateurs
     * Ne montre que les événements qui ne sont pas passés depuis plus d'un jour
     */
    public function findAllForAdmin(): array
    {
        // Date limite : aujourd'hui moins 1 jour
        $cutoffDate = new \DateTime('-1 day');
        
        return $this->createQueryBuilder('e')
            ->where('e.dateHeure >= :cutoffDate')
            ->setParameter('cutoffDate', $cutoffDate)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }
    
    /**
     * Trouve les événements archivés pour un utilisateur spécifique
     */
    public function findArchivedEventsForUser($user): array
    {
        return $this->createQueryBuilder('e')
            ->where('e.createdBy = :user')
            ->andWhere('e.archive = :archive')
            ->setParameter('user', $user)
            ->setParameter('archive', true)
            ->orderBy('e.dateHeure', 'DESC')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve un événement en conflit d'horaire pour un utilisateur
     * 
     * @param User $user L'utilisateur à vérifier
     * @param Event $newEvent L'événement à vérifier pour les conflits
     * @return Event|null L'événement en conflit ou null si aucun conflit
     */
    public function findConflictingEventForUser($user, $newEvent): ?Event
    {
        $newEventStart = $newEvent->getDateHeure();
        $newEventEnd = clone $newEventStart;
        $newEventEnd->modify('+' . $newEvent->getDuree() . ' minutes');
        
        // Méthode 1: Récupérer les événements via les invitations acceptées
        $qb1 = $this->createQueryBuilder('e');
        $qb1->join('e.invitations', 'i')
            ->where('i.email = :email')
            ->andWhere('e.id != :eventId')
            ->andWhere('e.archive = :archive')
            ->andWhere('i.status = :acceptedStatus')
            ->setParameter('email', $user->getEmail())
            ->setParameter('eventId', $newEvent->getId() ?: 0)
            ->setParameter('archive', false)
            ->setParameter('acceptedStatus', 'accepted');
            
        $events1 = $qb1->getQuery()->getResult();
        
        // Méthode 2: Récupérer les événements via les participations
        $qb2 = $this->createQueryBuilder('e');
        $qb2->join('e.participations', 'p')
            ->join('p.user', 'u')
            ->where('u.id = :userId')
            ->andWhere('e.id != :eventId')
            ->andWhere('e.archive = :archive')
            ->andWhere('p.invitationStatus = :acceptedStatus')
            ->setParameter('userId', $user->getId())
            ->setParameter('eventId', $newEvent->getId() ?: 0)
            ->setParameter('archive', false)
            ->setParameter('acceptedStatus', 'accepté');
            
        $events2 = $qb2->getQuery()->getResult();
        
        // Fusionner les résultats
        $events = array_merge($events1, $events2);
        
        // Vérifier manuellement les chevauchements
        foreach ($events as $existingEvent) {
            $existingStart = $existingEvent->getDateHeure();
            $existingEnd = clone $existingStart;
            $existingEnd->modify('+' . $existingEvent->getDuree() . ' minutes');
            
            // Conflit si chevauchement
            if ($newEventStart < $existingEnd && $newEventEnd > $existingStart) {
                return $existingEvent;
            }
        }
        
        return null;
    }

    /**
     * Trouve un événement en conflit d'horaire pour un utilisateur par email
     * 
     * @param string $userEmail L'email de l'utilisateur à vérifier
     * @param Event $newEvent L'événement à vérifier pour les conflits
     * @return Event|null L'événement en conflit ou null si aucun conflit
     */
    public function findConflictingEventForUserByEmail($userEmail, $newEvent): ?Event
    {
        $newEventStart = $newEvent->getDateHeure();
        $newEventEnd = clone $newEventStart;
        $newEventEnd->modify('+' . $newEvent->getDuree() . ' minutes');
        
        // Méthode 1: Récupérer les événements via les invitations acceptées
        $qb1 = $this->createQueryBuilder('e');
        $qb1->join('e.invitations', 'i')
            ->where('i.email = :email')
            ->andWhere('e.id != :eventId')
            ->andWhere('e.archive = :archive')
            ->andWhere('i.status = :acceptedStatus')
            ->setParameter('email', $userEmail)
            ->setParameter('eventId', $newEvent->getId() ?: 0)
            ->setParameter('archive', false)
            ->setParameter('acceptedStatus', 'accepted');
            
        $events1 = $qb1->getQuery()->getResult();
        
        // Méthode 2: Récupérer les événements via les participations (si l'utilisateur existe)
        $events2 = [];
        
        // Chercher l'utilisateur par email
        $userRepository = $this->getEntityManager()->getRepository('App\Entity\User');
        $user = $userRepository->findOneBy(['email' => $userEmail]);
        
        if ($user) {
            $qb2 = $this->createQueryBuilder('e');
            $qb2->join('e.participations', 'p')
                ->join('p.user', 'u')
                ->where('u.id = :userId')
                ->andWhere('e.id != :eventId')
                ->andWhere('e.archive = :archive')
                ->andWhere('p.invitationStatus = :acceptedStatus')
                ->setParameter('userId', $user->getId())
                ->setParameter('eventId', $newEvent->getId() ?: 0)
                ->setParameter('archive', false)
                ->setParameter('acceptedStatus', 'accepté');
                
            $events2 = $qb2->getQuery()->getResult();
        }
        
        // Fusionner les résultats
        $events = array_merge($events1, $events2);
        
        // Vérifier manuellement les chevauchements
        foreach ($events as $existingEvent) {
            $existingStart = $existingEvent->getDateHeure();
            $existingEnd = clone $existingStart;
            $existingEnd->modify('+' . $existingEvent->getDuree() . ' minutes');
            
            // Conflit si chevauchement
            if ($newEventStart < $existingEnd && $newEventEnd > $existingStart) {
                return $existingEvent;
            }
        }
        
        return null;
    }
}