<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour synchroniser les statuts d'invitation et corriger les incohérences
 */
final class Version20250819000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Synchronise les statuts d\'invitation et corrige les incohérences entre invitation et participation';
    }

    public function up(Schema $schema): void
    {
        // Mettre à jour les anciens statuts d'invitation vers les nouveaux
        $this->addSql("
            UPDATE invitation 
            SET status = CASE 
                WHEN status = 'en_attente' THEN 'pending'
                WHEN status = 'accepté' THEN 'accepted'
                WHEN status = 'refusé' THEN 'declined'
                WHEN status = 'expiré' THEN 'expired'
                ELSE status
            END
            WHERE status IN ('en_attente', 'accepté', 'refusé', 'expiré')
        ");

        // Mettre à jour les anciens statuts de participation vers les nouveaux
        $this->addSql("
            UPDATE participation 
            SET invitationStatus = CASE 
                WHEN invitationStatus = 'en_attente' THEN 'pending'
                WHEN invitationStatus = 'accepté' THEN 'accepted'
                WHEN invitationStatus = 'refusé' THEN 'declined'
                WHEN invitationStatus = 'expiré' THEN 'expired'
                ELSE invitationStatus
            END
            WHERE invitationStatus IN ('en_attente', 'accepté', 'refusé', 'expiré')
        ");

        // Synchroniser les statuts entre invitation et participation
        $this->addSql("
            UPDATE participation p
            INNER JOIN invitation i ON p.event_id = i.event_id 
                AND p.user_id = (SELECT id FROM users WHERE email = i.email LIMIT 1)
            SET p.invitationStatus = i.status
            WHERE p.invitationStatus != i.status 
                AND i.status IN ('pending', 'accepted', 'declined', 'expired', 'conflict')
        ");

        // Ajouter des contraintes de validation
        $this->addSql("
            ALTER TABLE invitation 
            MODIFY COLUMN status ENUM('pending', 'accepted', 'declined', 'expired', 'conflict') 
            DEFAULT 'pending' NOT NULL
        ");

        $this->addSql("
            ALTER TABLE participation 
            MODIFY COLUMN invitationStatus ENUM('pending', 'accepted', 'declined', 'expired', 'conflict') 
            DEFAULT 'pending' NOT NULL
        ");

        // Ajouter des index pour améliorer les performances
        $this->addSql("
            CREATE INDEX idx_invitation_status ON invitation (status)
        ");

        $this->addSql("
            CREATE INDEX idx_participation_invitation_status ON participation (invitationStatus)
        ");
    }

    public function down(Schema $schema): void
    {
        // Supprimer les index
        $this->addSql("DROP INDEX idx_invitation_status ON invitation");
        $this->addSql("DROP INDEX idx_participation_invitation_status ON participation");
        
        // Restaurer les colonnes originales
        $this->addSql("
            ALTER TABLE invitation 
            MODIFY COLUMN status VARCHAR(20) DEFAULT 'pending' NOT NULL
        ");

        $this->addSql("
            ALTER TABLE participation 
            MODIFY COLUMN invitationStatus VARCHAR(20) DEFAULT 'pending' NOT NULL
        ");
    }
}
