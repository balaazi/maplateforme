<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter la colonne invitationStatus à la table participation
 */
final class Version20250818230000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la colonne invitationStatus à la table participation et met à jour les anciens statuts';
    }

    public function up(Schema $schema): void
    {
        // Ajouter la colonne invitationStatus si elle n'existe pas
        $this->addSql("
            ALTER TABLE participation 
            ADD COLUMN invitationStatus VARCHAR(20) DEFAULT 'pending' 
            AFTER event_id
        ");

        // Mettre à jour les anciens statuts vers les nouveaux
        $this->addSql("
            UPDATE participation 
            SET invitationStatus = CASE 
                WHEN invitationStatus = 'en_attente' THEN 'pending'
                WHEN invitationStatus = 'accepté' THEN 'accepted'
                WHEN invitationStatus = 'refusé' THEN 'declined'
                ELSE 'pending'
            END
        ");

        // Ajouter un index sur invitationStatus pour améliorer les performances
        $this->addSql("
            CREATE INDEX idx_participation_invitation_status 
            ON participation (invitationStatus)
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer l'index
        $this->addSql("DROP INDEX idx_participation_invitation_status ON participation");
        
        // Supprimer la colonne
        $this->addSql("ALTER TABLE participation DROP COLUMN invitationStatus");
    }
}
