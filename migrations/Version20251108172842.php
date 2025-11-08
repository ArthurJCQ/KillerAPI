<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add avatar property to User entity
 */
final class Version20251108172842 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add avatar column to users table';
    }

    public function up(Schema $schema): void
    {
        // Add avatar column to users table with default value
        $this->addSql("ALTER TABLE users ADD avatar VARCHAR(255) NOT NULL DEFAULT 'captain'");
    }

    public function down(Schema $schema): void
    {
        // Remove avatar column from users table
        $this->addSql('ALTER TABLE users DROP COLUMN avatar');
    }
}
