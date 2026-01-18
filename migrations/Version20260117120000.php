<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add allow_spectators column to room table for spectator mode support
 */
final class Version20260117120000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add allow_spectators column to room table for spectator mode support';
    }

    public function up(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room ADD allow_spectators BOOLEAN NOT NULL DEFAULT FALSE');
    }

    public function down(Schema $schema): void
    {
        $this->addSql('ALTER TABLE room DROP COLUMN allow_spectators');
    }
}
