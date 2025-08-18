<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250714021000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout progressif du champ created_by_id à la table event (nullable, puis remplissage, puis NOT NULL + clé étrangère)';
    }

    public function up(Schema $schema): void
    {
        // 1. Ajouter la colonne nullable
        $this->addSql('ALTER TABLE event ADD created_by_id INT DEFAULT NULL');
        // 2. Remplir avec un utilisateur existant (id=1 par défaut)
        $this->addSql('UPDATE event SET created_by_id = 1 WHERE created_by_id IS NULL');
        // 3. Modifier la colonne en NOT NULL et ajouter la clé étrangère
        $this->addSql('ALTER TABLE event MODIFY created_by_id INT NOT NULL');
        $this->addSql('ALTER TABLE event ADD CONSTRAINT FK_EVENT_CREATED_BY FOREIGN KEY (created_by_id) REFERENCES users (id)');
        $this->addSql('CREATE INDEX IDX_EVENT_CREATED_BY ON event (created_by_id)');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event DROP FOREIGN KEY FK_EVENT_CREATED_BY');
        $this->addSql('DROP INDEX IDX_EVENT_CREATED_BY ON event');
        $this->addSql('ALTER TABLE event DROP created_by_id');
    }
} 