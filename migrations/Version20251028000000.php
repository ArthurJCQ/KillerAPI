<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactor secondary missions to use separate collection instead of boolean flag.
 * - Adds room_secondary_missions foreign key column
 * - Migrates existing secondary missions to use the new column
 * - Drops the is_secondary_mission boolean column
 */
final class Version20251028000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace is_secondary_mission boolean with room_secondary_missions foreign key for separate collections';
    }

    public function up(Schema $schema): void
    {
        // Add new room_secondary_missions column
        $this->addSql('ALTER TABLE mission ADD room_secondary_missions VARCHAR(5) DEFAULT NULL');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C_SECONDARY FOREIGN KEY (room_secondary_missions) REFERENCES room (id)');
        $this->addSql('CREATE INDEX IDX_9067F23C_SECONDARY ON mission (room_secondary_missions)');

        // Migrate data: move secondary missions from room_missions to room_secondary_missions
        $this->addSql('UPDATE mission SET room_secondary_missions = room_missions, room_missions = NULL WHERE is_secondary_mission = true');

        // Drop old is_secondary_mission column
        $this->addSql('ALTER TABLE mission DROP is_secondary_mission');
    }

    public function down(Schema $schema): void
    {
        // Add back is_secondary_mission column
        $this->addSql('ALTER TABLE mission ADD is_secondary_mission BOOLEAN DEFAULT false NOT NULL');

        // Migrate data back: move missions from room_secondary_missions back to room_missions with flag
        $this->addSql('UPDATE mission SET is_secondary_mission = true, room_missions = room_secondary_missions, room_secondary_missions = NULL WHERE room_secondary_missions IS NOT NULL');

        // Drop new column and index
        $this->addSql('DROP INDEX IDX_9067F23C_SECONDARY ON mission');
        $this->addSql('ALTER TABLE mission DROP FOREIGN KEY FK_9067F23C_SECONDARY');
        $this->addSql('ALTER TABLE mission DROP room_secondary_missions');
    }
}
