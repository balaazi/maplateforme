<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250712114815 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reminder (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, description LONGTEXT DEFAULT NULL, due_date DATETIME NOT NULL, is_done TINYINT(1) NOT NULL, is_triggered TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, triggered_at DATETIME DEFAULT NULL, type VARCHAR(50) NOT NULL, priority VARCHAR(20) NOT NULL, send_email TINYINT(1) NOT NULL, show_notification TINYINT(1) NOT NULL, play_sound TINYINT(1) NOT NULL, metadata JSON DEFAULT NULL, user_id INT NOT NULL, event_id INT DEFAULT NULL, INDEX IDX_40374F40A76ED395 (user_id), INDEX IDX_40374F4071F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder ADD CONSTRAINT FK_40374F40A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder ADD CONSTRAINT FK_40374F4071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminders DROP FOREIGN KEY FK_6D92B9D4A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminders DROP FOREIGN KEY FK_6D92B9D471F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reminders
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP must_change_password, DROP password_set_at
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE reminders (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description LONGTEXT CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, due_date DATETIME NOT NULL, type VARCHAR(50) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, priority VARCHAR(20) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, is_triggered TINYINT(1) NOT NULL, is_done TINYINT(1) NOT NULL, created_at DATETIME NOT NULL, triggered_at DATETIME DEFAULT NULL, metadata JSON DEFAULT NULL, user_id INT NOT NULL, event_id INT NOT NULL, INDEX IDX_6D92B9D4A76ED395 (user_id), INDEX IDX_6D92B9D471F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminders ADD CONSTRAINT FK_6D92B9D4A76ED395 FOREIGN KEY (user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminders ADD CONSTRAINT FK_6D92B9D471F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder DROP FOREIGN KEY FK_40374F40A76ED395
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE reminder DROP FOREIGN KEY FK_40374F4071F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE reminder
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD must_change_password TINYINT(1) DEFAULT 0 NOT NULL, ADD password_set_at DATETIME DEFAULT NULL
        SQL);
    }
}
