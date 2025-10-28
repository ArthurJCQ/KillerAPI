<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Refactor secondary missions to use separate collection with join table instead of boolean flag.
 * - Creates room_secondary_missions join table
 * - Migrates existing secondary missions to the join table
 * - Drops the is_secondary_mission boolean column
 */
final class Version20251028000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Replace is_secondary_mission boolean with room_secondary_missions join table for separate collections';
    }

    public function up(Schema $schema): void
    {
        // Create join table for secondary missions
        $this->addSql('CREATE TABLE room_secondary_missions (room_id VARCHAR(5) NOT NULL, mission_id INT NOT NULL, INDEX IDX_SECONDARY_ROOM (room_id), UNIQUE INDEX UNIQ_SECONDARY_MISSION (mission_id), PRIMARY KEY(room_id, mission_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
        $this->addSql('ALTER TABLE room_secondary_missions ADD CONSTRAINT FK_SECONDARY_ROOM FOREIGN KEY (room_id) REFERENCES room (id)');
        $this->addSql('ALTER TABLE room_secondary_missions ADD CONSTRAINT FK_SECONDARY_MISSION FOREIGN KEY (mission_id) REFERENCES mission (id)');

        // Migrate data: move secondary missions to join table
        $this->addSql('INSERT INTO room_secondary_missions (room_id, mission_id) SELECT room_missions, id FROM mission WHERE is_secondary_mission = true');

        // Drop old is_secondary_mission column
        $this->addSql('ALTER TABLE mission DROP is_secondary_mission');
    }

    public function down(Schema $schema): void
    {
        // Add back is_secondary_mission column
        $this->addSql('ALTER TABLE mission ADD is_secondary_mission BOOLEAN DEFAULT false NOT NULL');

        // Migrate data back: mark missions in join table as secondary
        $this->addSql('UPDATE mission SET is_secondary_mission = true WHERE id IN (SELECT mission_id FROM room_secondary_missions)');

        // Drop join table
        $this->addSql('DROP TABLE room_secondary_missions');
    }
}
