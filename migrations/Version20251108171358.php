<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add room property to User entity for room context tracking
 */
final class Version20251108171358 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add room_id column to users table to track user\'s current room context';
    }

    public function up(Schema $schema): void
    {
        // Add room_id column to users table
        $this->addSql('ALTER TABLE users ADD room_id INTEGER DEFAULT NULL');
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E954177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_1483A5E954177093 ON users (room_id)');
    }

    public function down(Schema $schema): void
    {
        // Remove room_id column from users table
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E954177093');
        $this->addSql('DROP INDEX IDX_1483A5E954177093');
        $this->addSql('ALTER TABLE users DROP COLUMN room_id');
    }
}
