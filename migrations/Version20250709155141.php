<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250709155141 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users ADD enable_sound_notifications TINYINT(1) DEFAULT 1 NOT NULL, ADD enable_visual_notifications TINYINT(1) DEFAULT 1 NOT NULL, ADD reminder_frequency INT DEFAULT 1 NOT NULL, ADD notification_priority VARCHAR(20) DEFAULT 'normal' NOT NULL
        SQL);
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql(<<<'SQL'
            ALTER TABLE users DROP enable_sound_notifications, DROP enable_visual_notifications, DROP reminder_frequency, DROP notification_priority
        SQL);
    }
}
