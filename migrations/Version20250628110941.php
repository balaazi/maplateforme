<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250628110941 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE departements (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) NOT NULL, code VARCHAR(10) NOT NULL, description LONGTEXT DEFAULT NULL, responsable VARCHAR(100) DEFAULT NULL, email_contact VARCHAR(100) DEFAULT NULL, telephone VARCHAR(20) DEFAULT NULL, localisation VARCHAR(255) DEFAULT NULL, budget_annuel INT DEFAULT 0 NOT NULL, actif TINYINT(1) DEFAULT 1 NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, UNIQUE INDEX UNIQ_CF7489B26C6E55B5 (nom), UNIQUE INDEX UNIQ_CF7489B277153098 (code), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD departement_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD CONSTRAINT FK_3BAE0AA7CCF9E01E FOREIGN KEY (departement_id) REFERENCES departements (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_3BAE0AA7CCF9E01E ON event (departement_id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation DROP personal_message, DROP role, DROP send_reminder, DROP can_invite_others, DROP reminder_sent_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD departement_id INT DEFAULT NULL, DROP departement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT FK_1483A5E9CCF9E01E FOREIGN KEY (departement_id) REFERENCES departements (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1483A5E9CCF9E01E ON users (departement_id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE departements
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP FOREIGN KEY FK_3BAE0AA7CCF9E01E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_3BAE0AA7CCF9E01E ON event
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP departement_id
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation ADD personal_message LONGTEXT DEFAULT NULL, ADD role VARCHAR(50) DEFAULT NULL, ADD send_reminder TINYINT(1) NOT NULL, ADD can_invite_others TINYINT(1) NOT NULL, ADD reminder_sent_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9CCF9E01E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1483A5E9CCF9E01E ON users
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD departement VARCHAR(100) DEFAULT NULL, DROP departement_id
        SQL);
    }
}
