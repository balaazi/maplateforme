<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250605035625 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE notification (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, message LONGTEXT DEFAULT NULL, is_read TINYINT(1) NOT NULL, created_at VARCHAR(255) NOT NULL, type VARCHAR(50) NOT NULL, user_id INT NOT NULL, event_id INT DEFAULT NULL, INDEX IDX_BF5476CAA76ED395 (user_id), INDEX IDX_BF5476CA71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CA71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder DROP FOREIGN KEY FK_40374F4071F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder DROP FOREIGN KEY FK_40374F409D1C3019
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reminder
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, send_at DATETIME NOT NULL, type VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, sent TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, sent_at DATETIME DEFAULT NULL, message LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, status VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, event_id INT NOT NULL, participant_id INT NOT NULL, INDEX IDX_40374F409D1C3019 (participant_id), INDEX IDX_40374F4071F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder ADD CONSTRAINT FK_40374F4071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder ADD CONSTRAINT FK_40374F409D1C3019 FOREIGN KEY (participant_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CA71F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE notification
        SQL);
    }
}
