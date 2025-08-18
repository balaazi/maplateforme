<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour ajouter les tables ProcesVerbal et ActionPV - Version simplifiée
 */
final class Version20250108140000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute les tables pour les procès-verbaux et actions - version simplifiée';
    }

    public function up(Schema $schema): void
    {
        // Créer d'abord la table proces_verbal sans contraintes
        $this->addSql('CREATE TABLE proces_verbal (
            id INT AUTO_INCREMENT NOT NULL,
            event_id INT NOT NULL,
            redacteur_id INT NOT NULL,
            date_heure DATETIME NOT NULL,
            participants LONGTEXT NOT NULL,
            points_abordes LONGTEXT NOT NULL,
            decisions_prises LONGTEXT NOT NULL,
            date_creation DATETIME NOT NULL,
            date_modification DATETIME DEFAULT NULL,
            finalise TINYINT(1) DEFAULT 0 NOT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Créer la table action_pv sans contraintes
        $this->addSql('CREATE TABLE action_pv (
            id INT AUTO_INCREMENT NOT NULL,
            proces_verbal_id INT NOT NULL,
            responsable_id INT DEFAULT NULL,
            description LONGTEXT NOT NULL,
            responsable_nom VARCHAR(255) DEFAULT NULL,
            delai DATE DEFAULT NULL,
            statut VARCHAR(50) DEFAULT \'en_attente\' NOT NULL,
            notes LONGTEXT DEFAULT NULL,
            PRIMARY KEY(id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajouter les index
        $this->addSql('CREATE INDEX IDX_proces_verbal_event ON proces_verbal (event_id)');
        $this->addSql('CREATE INDEX IDX_proces_verbal_redacteur ON proces_verbal (redacteur_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_proces_verbal_event ON proces_verbal (event_id)');
        $this->addSql('CREATE INDEX IDX_action_pv_proces_verbal ON action_pv (proces_verbal_id)');
        $this->addSql('CREATE INDEX IDX_action_pv_responsable ON action_pv (responsable_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE action_pv');
        $this->addSql('DROP TABLE proces_verbal');
    }
}