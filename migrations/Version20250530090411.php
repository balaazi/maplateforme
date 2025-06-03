<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250530090411 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE collaborative_note (id INT AUTO_INCREMENT NOT NULL, title VARCHAR(255) NOT NULL, content LONGTEXT NOT NULL, created_at DATETIME NOT NULL, updated_at DATETIME NOT NULL, event_id INT NOT NULL, created_by_id INT NOT NULL, INDEX IDX_1A99550071F7E88B (event_id), INDEX IDX_1A995500B03A8386 (created_by_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaborative_note ADD CONSTRAINT FK_1A99550071F7E88B FOREIGN KEY (event_id) REFERENCES event (id)
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaborative_note ADD CONSTRAINT FK_1A995500B03A8386 FOREIGN KEY (created_by_id) REFERENCES users (id)
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE collaborative_note DROP FOREIGN KEY FK_1A99550071F7E88B
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE collaborative_note DROP FOREIGN KEY FK_1A995500B03A8386
        SQL);
        $this->addSql(<<<'SQL'
            DROP TABLE collaborative_note
        SQL);
    }
}
