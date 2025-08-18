<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

final class Version20250714020000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Ajout du champ created_by_id à la table event (clé étrangère vers users)';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE event ADD created_by_id INT NOT NULL');
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