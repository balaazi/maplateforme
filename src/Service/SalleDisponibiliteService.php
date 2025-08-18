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

        // Vérifier les horaires spécifiques par jour de la semaine
        if (!$this->estDansHorairesParJour($salle, $debut, $fin)) {
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
     * Récupère le statut d'une salle pour une date spécifique
     */
    public function getStatutActuelPourDate(Salle $salle, \DateTimeInterface $date): array
    {
        if (!$salle->isDisponible()) {
            return [
                'statut' => 'desactivee',
                'libelle' => 'Désactivée',
                'couleur' => 'danger'
            ];
        }

        $maintenant = new \DateTime();
        
        // Convertir en DateTime si nécessaire pour utiliser setTime
        if ($date instanceof \DateTimeInterface && !($date instanceof \DateTime)) {
            $dateDebut = new \DateTime($date->format('Y-m-d H:i:s'));
            $dateFin = new \DateTime($date->format('Y-m-d H:i:s'));
        } else {
            $dateDebut = clone $date;
            $dateFin = clone $date;
        }
        
        $dateDebut->setTime(0, 0, 0);
        $dateFin->setTime(23, 59, 59);

        // Si c'est aujourd'hui, vérifier s'il y a une réservation en cours maintenant
        if ($date->format('Y-m-d') === $maintenant->format('Y-m-d')) {
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
        }

        // Chercher les réservations pour cette date
        $reservationsPourDate = $this->reservationRepository->findReservationsPourJour($salle, $date);

        if (!empty($reservationsPourDate)) {
            // Il y a des réservations ce jour-là
            if ($date->format('Y-m-d') === $maintenant->format('Y-m-d')) {
                // Pour aujourd'hui, chercher la prochaine réservation
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
            } else {
                // Pour une date future, la salle est réservée
                return [
                    'statut' => 'reservee',
                    'libelle' => 'Réservée',
                    'couleur' => 'warning',
                    'reservations' => $reservationsPourDate
                ];
            }
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
     * Vérifie si la période est dans les horaires spécifiques par jour de la semaine
     */
    private function estDansHorairesParJour(Salle $salle, \DateTimeInterface $debut, \DateTimeInterface $fin): bool
    {
        $horairesParJour = $salle->getHorairesParJour();
        
        if (!$horairesParJour) {
            return true; // Si pas d'horaires par jour définis, considérer comme toujours ouvert
        }

        // Vérifier chaque jour de la période
        $dateCourante = new \DateTime($debut->format('Y-m-d'));
        $dateFin = new \DateTime($fin->format('Y-m-d'));
        
        while ($dateCourante < $dateFin) {
            $jourSemaine = strtolower($dateCourante->format('l')); // lundi, mardi, etc.
            
            // Vérifier si le jour a des horaires définis
            if (!isset($horairesParJour[$jourSemaine]) || $horairesParJour[$jourSemaine] === null) {
                // Le jour est fermé
                return false;
            }
            
            $horairesJour = $horairesParJour[$jourSemaine];
            
            // Si la réservation commence ce jour-là
            if ($dateCourante->format('Y-m-d') === $debut->format('Y-m-d')) {
                $heureDebut = $debut->format('H:i');
                if ($heureDebut < $horairesJour['debut']) {
                    return false; // Trop tôt
                }
            }
            
            // Si la réservation se termine ce jour-là
            if ($dateCourante->format('Y-m-d') === $fin->format('Y-m-d')) {
                $heureFin = $fin->format('H:i');
                if ($heureFin > $horairesJour['fin']) {
                    return false; // Trop tard
                }
            }
            
            // Passer au jour suivant
            $dateCourante->add(new \DateInterval('P1D'));
        }
        
        return true;
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