<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration pour créer la table document
 */
final class Version20250114210000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajoute la table document pour les fichiers uploadés lors des événements';
    }

    public function up(Schema $schema): void
    {
        // Vérifier si la table document existe déjà
        $this->addSql('CREATE TABLE IF NOT EXISTS document (
            id INT AUTO_INCREMENT NOT NULL,
            event_id INT NOT NULL,
            file_name VARCHAR(255) DEFAULT NULL,
            created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\',
            PRIMARY KEY(id),
            INDEX IDX_document_event (event_id)
        ) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');

        // Ajouter la contrainte de clé étrangère si elle n'existe pas
        $this->addSql('ALTER TABLE document ADD CONSTRAINT FK_document_event_id 
                       FOREIGN KEY (event_id) REFERENCES event (id) ON DELETE CASCADE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('DROP TABLE IF EXISTS document');
    }
}
