<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250624202235 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation ADD nombre_participants INT DEFAULT NULL, ADD notes LONGTEXT DEFAULT NULL, ADD contact_telephone VARCHAR(255) DEFAULT NULL, ADD contact_email VARCHAR(255) DEFAULT NULL, ADD recurrente TINYINT(1) NOT NULL, ADD type_recurrence VARCHAR(50) DEFAULT NULL, ADD fin_recurrence DATE DEFAULT NULL, ADD date_modification DATETIME DEFAULT NULL, ADD modifie_par VARCHAR(255) DEFAULT NULL
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salle ADD description LONGTEXT DEFAULT NULL, ADD localisation VARCHAR(100) DEFAULT NULL, ADD equipements JSON DEFAULT NULL, ADD type VARCHAR(50) DEFAULT NULL, ADD superficie NUMERIC(10, 2) DEFAULT NULL, ADD photo VARCHAR(255) DEFAULT NULL, ADD accessibilite TINYINT(1) NOT NULL, ADD tarif NUMERIC(5, 2) DEFAULT NULL, ADD priorite INT DEFAULT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE reservation DROP nombre_participants, DROP notes, DROP contact_telephone, DROP contact_email, DROP recurrente, DROP type_recurrence, DROP fin_recurrence, DROP date_modification, DROP modifie_par
        SQL);
        $this->addSql(<<<'SQL'
            ALTER TABLE salle DROP description, DROP localisation, DROP equipements, DROP type, DROP superficie, DROP photo, DROP accessibilite, DROP tarif, DROP priorite
        SQL);
    }
}
