<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250623104005 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE gestion_salle (id INT AUTO_INCREMENT NOT NULL, responsable VARCHAR(255) NOT NULL, date_gestion DATETIME NOT NULL, commentaire VARCHAR(500) DEFAULT NULL, statut VARCHAR(50) NOT NULL, salle_id INT NOT NULL, INDEX IDX_38811BFDC304035 (salle_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE gestion_salle ADD CONSTRAINT FK_38811BFDC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7DC304035
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3BAE0AA7DC304035 ON event
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP salle_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE gestion_salle DROP FOREIGN KEY FK_38811BFDC304035
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE gestion_salle
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD salle_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7DC304035 FOREIGN KEY (salle_id) REFERENCES salle (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA7DC304035 ON event (salle_id)
        SQL);
    }
}
