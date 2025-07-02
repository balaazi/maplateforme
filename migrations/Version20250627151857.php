<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627151857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation ADD personal_message LONGTEXT DEFAULT NULL, ADD role VARCHAR(50) DEFAULT NULL, ADD send_reminder TINYINT(1) NOT NULL, ADD can_invite_others TINYINT(1) NOT NULL, ADD reminder_sent_at DATETIME DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP FOREIGN KEY FK_BF5476CAA35D7AF0
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_BF5476CAA35D7AF0 ON notification
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification DROP invitation_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation DROP personal_message, DROP role, DROP send_reminder, DROP can_invite_others, DROP reminder_sent_at
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD invitation_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA35D7AF0 FOREIGN KEY (invitation_id) REFERENCES invitation (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_BF5476CAA35D7AF0 ON notification (invitation_id)
        SQL);
    }
}
