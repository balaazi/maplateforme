<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250514112356 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE event_file (id INT AUTO_INCREMENT NOT NULL, name VARCHAR(255) NOT NULL, google_drive_file_id VARCHAR(255) NOT NULL, event_id INT NOT NULL, INDEX IDX_93547ABB71F7E88B (event_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event_file ADD CONSTRAINT FK_93547ABB71F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event ADD google_drive_folder_id VARCHAR(255) DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE event_file DROP FOREIGN KEY FK_93547ABB71F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE event_file
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE event DROP google_drive_folder_id
        SQL);
    }
}
