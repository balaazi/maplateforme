<?php

namespace App\Repository;

use App\Entity\Reservation;
use App\Entity\Salle;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Reservation>
 */
class ReservationRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Reservation::class);
    }

    /**
     * Trouve les réservations qui entrent en conflit avec une période donnée
     */
    public function findReservationsConflictuelles(Salle $salle, \DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut < :fin')
            ->andWhere('r.dateFin > :debut')
            ->setParameter('salle', $salle)
            ->setParameter('statut', 'confirmee')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve la réservation actuelle d'une salle
     */
    public function findReservationActuelle(Salle $salle, \DateTimeInterface $maintenant): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut <= :maintenant')
            ->andWhere('r.dateFin > :maintenant')
            ->setParameter('salle', $salle)
            ->setParameter('statut', 'confirmee')
            ->setParameter('maintenant', $maintenant)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve la prochaine réservation d'une salle
     */
    public function findProchaineReservation(Salle $salle, \DateTimeInterface $maintenant): ?Reservation
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut > :maintenant')
            ->orderBy('r.dateDebut', 'ASC')
            ->setParameter('salle', $salle)
            ->setParameter('statut', 'confirmee')
            ->setParameter('maintenant', $maintenant)
            ->setMaxResults(1)
            ->getQuery()
            ->getOneOrNullResult();
    }

    /**
     * Trouve les réservations d'une salle pour un jour donné
     */
    public function findReservationsPourJour(Salle $salle, \DateTimeInterface $date): array
    {
        $debut = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 00:00:00');
        $fin = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 23:59:59');

        return $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut >= :debut')
            ->andWhere('r.dateDebut <= :fin')
            ->orderBy('r.dateDebut', 'ASC')
            ->setParameter('salle', $salle)
            ->setParameter('statut', 'confirmee')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve toutes les salles avec leur statut de disponibilité
     */
    public function getSallesAvecDisponibilite(): array
    {
        $maintenant = new \DateTime();
        
        return $this->createQueryBuilder('r')
            ->select('s.id', 's.nom', 's.capacite', 's.disponible', 'r.dateDebut', 'r.dateFin', 'r.reservePar')
            ->leftJoin('r.salle', 's')
            ->where('r.dateDebut <= :maintenant')
            ->andWhere('r.dateFin > :maintenant')
            ->andWhere('r.statut = :statut')
            ->setParameter('maintenant', $maintenant)
            ->setParameter('statut', 'confirmee')
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations d'aujourd'hui
     */
    public function getReservationsAujourdhui(): array
    {
        $debut = new \DateTime('today');
        $fin = new \DateTime('tomorrow');

        return $this->createQueryBuilder('r')
            ->andWhere('r.statut = :statut')
            ->andWhere('r.dateDebut >= :debut')
            ->andWhere('r.dateDebut < :fin')
            ->setParameter('statut', 'confirmee')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Trouve les réservations pour une période donnée
     */
    public function findByPeriode(\DateTimeInterface $debut, \DateTimeInterface $fin): array
    {
        return $this->createQueryBuilder('r')
            ->andWhere('r.dateDebut >= :debut')
            ->andWhere('r.dateDebut <= :fin')
            ->orderBy('r.dateDebut', 'ASC')
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->getQuery()
            ->getResult();
    }

    /**
     * Obtient toutes les réservations pour le calendrier (format JSON)
     */
    public function getCalendarEvents(): array
    {
        $reservations = $this->createQueryBuilder('r')
            ->select('r.id', 'r.dateDebut', 'r.dateFin', 'r.motif', 'r.reservePar', 'r.statut', 's.nom as salle_nom', 's.id as salle_id')
            ->join('r.salle', 's')
            ->leftJoin('r.event', 'e')
            ->andWhere('r.statut = :statut')
            ->andWhere('e.status IS NULL OR e.status != :cancelled')
            ->setParameter('statut', 'confirmee')
            ->setParameter('cancelled', 'annulé')
            ->getQuery()
            ->getResult();

        $events = [];
        foreach ($reservations as $reservation) {
            $events[] = [
                'id' => $reservation['id'],
                'title' => $reservation['motif'],
                'start' => (new \DateTime($reservation['dateDebut']))->format('c'),
                'end' => (new \DateTime($reservation['dateFin']))->format('c'),
                'extendedProps' => [
                    'reservePar' => $reservation['reservePar'],
                    'salle' => $reservation['salle_nom'],
                    'salleId' => $reservation['salle_id'],
                    'statut' => $reservation['statut'],
                    'motif' => $reservation['motif']
                ]
            ];
        }

        return $events;
    }

    /**
     * Obtient les salles les plus utilisées
     */
    public function getSallesPlusUtilisees(int $limit = 5): array
    {
        return $this->createQueryBuilder('r')
            ->select('s.nom as salle_nom', 'COUNT(r.id) as total_reservations')
            ->join('r.salle', 's')
            ->andWhere('r.statut = :statut')
            ->setParameter('statut', 'confirmee')
            ->groupBy('s.id')
            ->orderBy('total_reservations', 'DESC')
            ->setMaxResults($limit)
            ->getQuery()
            ->getResult();
    }

    /**
     * Calcule le total d'heures réservées pour une période
     */
    public function getTotalHeuresReservees(\DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $reservations = $this->findByPeriode($debut, $fin);
        $totalHeures = 0;

        foreach ($reservations as $reservation) {
            $duree = $reservation->getDateDebut()->diff($reservation->getDateFin());
            $totalHeures += $duree->h + ($duree->i / 60);
        }

        return $totalHeures;
    }

    /**
     * Calcule le total d'heures réservées pour une salle spécifique
     */
    public function getTotalHeuresReserveesSalle(Salle $salle, \DateTimeInterface $debut, \DateTimeInterface $fin): float
    {
        $reservations = $this->createQueryBuilder('r')
            ->andWhere('r.salle = :salle')
            ->andWhere('r.dateDebut >= :debut')
            ->andWhere('r.dateDebut <= :fin')
            ->andWhere('r.statut = :statut')
            ->setParameter('salle', $salle)
            ->setParameter('debut', $debut)
            ->setParameter('fin', $fin)
            ->setParameter('statut', 'confirmee')
            ->getQuery()
            ->getResult();

        $totalHeures = 0;
        foreach ($reservations as $reservation) {
            $duree = $reservation->getDateDebut()->diff($reservation->getDateFin());
            $totalHeures += $duree->h + ($duree->i / 60);
        }

        return $totalHeures;
    }
}
