<?php

namespace App\Service;

use App\Enum\InvitationStatus;

class InvitationStatusService
{
    /**
     * Retourne le texte lisible d'un statut d'invitation
     */
    public function getStatusText(string $status): string
    {
        return match ($status) {
            InvitationStatus::ACCEPTED->value => 'Acceptée',
            InvitationStatus::DECLINED->value => 'Refusée',
            InvitationStatus::PENDING->value => 'En attente',
            InvitationStatus::EXPIRED->value => 'Expirée',
            InvitationStatus::CONFLICT->value => 'Conflit horaire',
            default => 'Non défini'
        };
    }

    /**
     * Retourne la classe CSS pour un statut d'invitation
     */
    public function getStatusClass(string $status): string
    {
        return match ($status) {
            InvitationStatus::ACCEPTED->value => 'status-accepted',
            InvitationStatus::DECLINED->value => 'status-declined',
            InvitationStatus::PENDING->value => 'status-pending',
            InvitationStatus::EXPIRED->value => 'status-expired',
            InvitationStatus::CONFLICT->value => 'status-conflict',
            default => 'status-unknown'
        };
    }

    /**
     * Vérifie si un statut permet la participation à l'événement
     */
    public function canParticipate(string $status): bool
    {
        return $status === InvitationStatus::ACCEPTED->value;
    }

    /**
     * Vérifie si un statut est final (ne peut plus être modifié)
     */
    public function isFinalStatus(string $status): bool
    {
        return in_array($status, [
            InvitationStatus::ACCEPTED->value,
            InvitationStatus::DECLINED->value,
            InvitationStatus::EXPIRED->value,
            InvitationStatus::CONFLICT->value
        ]);
    }
}
