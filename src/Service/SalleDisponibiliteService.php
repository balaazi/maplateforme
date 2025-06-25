<?php

namespace App\Service;

use App\Entity\Salle;
use App\Entity\Reservation;
use App\Repository\ReservationRepository;
use Doctrine\ORM\EntityManagerInterface;

class SalleDisponibiliteService
{
    private ReservationRepository $reservationRepository;
    private EntityManagerInterface $entityManager;

    public function __construct(
        ReservationRepository $reservationRepository,
        EntityManagerInterface $entityManager
    ) {
        $this->reservationRepository = $reservationRepository;
        $this->entityManager = $entityManager;
    }

    /**
     * Vérifie si une salle est disponible à un moment donné
     */
    public function estDisponible(Salle $salle, \DateTimeInterface $debut, \DateTimeInterface $fin): bool
    {
        // Vérifier que la salle n'est pas désactivée
        if (!$salle->isDisponible()) {
            return false;
        }

        // Vérifier que la période demandée est dans les heures d'ouverture de la salle
        if (!$this->estDansHeuresOuverture($salle, $debut, $fin)) {
            return false;
        }

        // Chercher les réservations qui chevauchent avec la période demandée
        $reservationsConflictuelles = $this->reservationRepository->findReservationsConflictuelles(
            $salle, 
            $debut, 
            $fin
        );

        return count($reservationsConflictuelles) === 0;
    }

    /**
     * Vérifie si une salle est actuellement occupée
     */
    public function estOccupee(Salle $salle): bool
    {
        $maintenant = new \DateTime();
        
        $reservationActuelle = $this->reservationRepository->findReservationActuelle($salle, $maintenant);
        
        return $reservationActuelle !== null;
    }

    /**
     * Récupère le statut actuel d'une salle
     */
    public function getStatutActuel(Salle $salle): array
    {
        if (!$salle->isDisponible()) {
            return [
                'statut' => 'desactivee',
                'libelle' => 'Désactivée',
                'couleur' => 'danger'
            ];
        }

        $maintenant = new \DateTime();
        $reservationActuelle = $this->reservationRepository->findReservationActuelle($salle, $maintenant);

        if ($reservationActuelle) {
            return [
                'statut' => 'occupee',
                'libelle' => 'Occupée',
                'couleur' => 'danger',
                'reservation' => $reservationActuelle,
                'fin_occupation' => $reservationActuelle->getDateFin()
            ];
        }

        // Vérifier s'il y a une réservation prochaine
        $prochaineReservation = $this->reservationRepository->findProchaineReservation($salle, $maintenant);

        if ($prochaineReservation) {
            return [
                'statut' => 'libre_temporaire',
                'libelle' => 'Libre (réservée bientôt)',
                'couleur' => 'warning',
                'prochaine_reservation' => $prochaineReservation,
                'debut_occupation' => $prochaineReservation->getDateDebut()
            ];
        }

        return [
            'statut' => 'libre',
            'libelle' => 'Libre',
            'couleur' => 'success'
        ];
    }

    /**
     * Récupère les créneaux libres d'une salle pour une journée donnée
     */
    public function getCreneauxLibres(Salle $salle, \DateTimeInterface $date): array
    {
        $debut = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 08:00:00');
        $fin = \DateTime::createFromFormat('Y-m-d H:i:s', $date->format('Y-m-d') . ' 18:00:00');

        $reservations = $this->reservationRepository->findReservationsPourJour($salle, $date);

        $creneauxLibres = [];
        $derniereFin = $debut;

        foreach ($reservations as $reservation) {
            // S'il y a un créneau libre avant cette réservation
            if ($reservation->getDateDebut() > $derniereFin) {
                $creneauxLibres[] = [
                    'debut' => clone $derniereFin,
                    'fin' => clone $reservation->getDateDebut()
                ];
            }
            $derniereFin = $reservation->getDateFin();
        }

        // S'il y a encore du temps libre après la dernière réservation
        if ($derniereFin < $fin) {
            $creneauxLibres[] = [
                'debut' => clone $derniereFin,
                'fin' => clone $fin
            ];
        }

        return $creneauxLibres;
    }

    /**
     * Vérifie si la période est dans les heures d'ouverture de la salle
     */
    private function estDansHeuresOuverture(Salle $salle, \DateTimeInterface $debut, \DateTimeInterface $fin): bool
    {
        // Vérifier avec les heures de début et fin de réservation de la salle
        $heureOuverture = $salle->getDebutReservation();
        $heureFermeture = $salle->getFinReservation();

        if (!$heureOuverture || !$heureFermeture) {
            return true; // Si pas d'heures définies, considérer comme toujours ouvert
        }

        // Extraire uniquement les heures (ignorer les dates)
        $debutHeure = $debut->format('H:i');
        $finHeure = $fin->format('H:i');
        $ouvertureHeure = $heureOuverture->format('H:i');
        $fermetureHeure = $heureFermeture->format('H:i');

        // Gérer le cas où la fermeture est le lendemain (ex: 22:00 - 02:00)
        if ($fermetureHeure < $ouvertureHeure) {
            // Cas de passage minuit : salle ouverte de 22:00 à 02:00
            return ($debutHeure >= $ouvertureHeure || $debutHeure <= $fermetureHeure) &&
                   ($finHeure >= $ouvertureHeure || $finHeure <= $fermetureHeure) &&
                   ($debutHeure <= $finHeure || $debutHeure >= $ouvertureHeure);
        }

        // Cas normal : salle ouverte de 08:00 à 18:00
        return $debutHeure >= $ouvertureHeure && $finHeure <= $fermetureHeure;
    }

    /**
     * Trouve une salle disponible avec la capacité demandée
     */
    public function trouverSalleDisponible(int $capaciteMinimum, \DateTimeInterface $debut, \DateTimeInterface $fin): ?Salle
    {
        $salles = $this->entityManager->getRepository(Salle::class)
            ->findBy(['disponible' => true]);

        foreach ($salles as $salle) {
            if ($salle->getCapacite() >= $capaciteMinimum && 
                $this->estDisponible($salle, $debut, $fin)) {
                return $salle;
            }
        }

        return null;
    }
} 