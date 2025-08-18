<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20250719000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add missing notify_by_sms column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add the missing notify_by_sms column
        $this->addSql('ALTER TABLE users ADD notify_by_sms TINYINT(1) DEFAULT 0 NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // Remove the notify_by_sms column
        $this->addSql('ALTER TABLE users DROP notify_by_sms');
    }
} 