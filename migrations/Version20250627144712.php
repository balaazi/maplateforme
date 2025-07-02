<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627144712 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation DROP FOREIGN KEY FK_F11D61A2C58DAD6E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_F11D61A2C58DAD6E ON invitation
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation DROP invited_user_id
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

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation ADD invited_user_id INT DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE invitation ADD CONSTRAINT FK_F11D61A2C58DAD6E FOREIGN KEY (invited_user_id) REFERENCES users (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_F11D61A2C58DAD6E ON invitation (invited_user_id)
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
}
