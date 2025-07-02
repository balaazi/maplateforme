<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250627131230 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            DROP TABLE departements
        SQL);
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
            ALTER TABLE users DROP FOREIGN KEY FK_1483A5E9CCF9E01E
        SQL);
        $this->addSql(<<<'SQL'
            DROP INDEX IDX_1483A5E9CCF9E01E ON users
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD departement VARCHAR(100) DEFAULT NULL, DROP departement_id
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            CREATE TABLE departements (id INT AUTO_INCREMENT NOT NULL, nom VARCHAR(100) CHARACTER SET utf8mb4 NOT NULL COLLATE `utf8mb4_general_ci`, description LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, code VARCHAR(50) CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_general_ci`, created_at DATETIME NOT NULL, updated_at DATETIME DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_general_ci` ENGINE = InnoDB COMMENT = '' 
        SQL);
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
            ALTER TABLE users ADD departement_id INT DEFAULT NULL, DROP departement
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD CONSTRAINT FK_1483A5E9CCF9E01E FOREIGN KEY (departement_id) REFERENCES departements (id)
        SQL);
        $this->addSql(<<<'SQL'
            CREATE INDEX IDX_1483A5E9CCF9E01E ON users (departement_id)
        SQL);
    }
}
