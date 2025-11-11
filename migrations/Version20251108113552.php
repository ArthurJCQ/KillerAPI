<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add User entity with OAuth, room context, and getCurrentUserPlayer support
 */
final class Version20251108113552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table with OAuth, room context, and avatar support; link player to user; update authorization model';
    }

    public function up(Schema $schema): void
    {
        // Create users table with all properties including room context and avatar
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE users (
            id INT NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            name VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            google_id VARCHAR(255) DEFAULT NULL,
            apple_id VARCHAR(255) DEFAULT NULL,
            room_id VARCHAR(5) DEFAULT NULL,
            avatar VARCHAR(255) NOT NULL DEFAULT \'captain\',
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9772E836A ON users (google_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E96B01BC5B ON users (apple_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E954177093 ON users (room_id)');
        $this->addSql('COMMENT ON COLUMN users.roles IS \'(DC2Type:json)\'');

        // Add foreign key for room_id
        $this->addSql('ALTER TABLE users ADD CONSTRAINT FK_1483A5E954177093 FOREIGN KEY (room_id) REFERENCES room (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');

        // Add user_id column to player table
        $this->addSql('ALTER TABLE player ADD user_id INT DEFAULT NULL');

        // Add isAdmin and isMaster columns to player table
        $this->addSql('ALTER TABLE player ADD is_admin BOOLEAN NOT NULL DEFAULT FALSE');
        $this->addSql('ALTER TABLE player ADD is_master BOOLEAN NOT NULL DEFAULT FALSE');

        // Migrate existing player data to users
        $this->addSql('
            INSERT INTO users (id, name, avatar, room_id, roles)
            SELECT
                id,
                name,
                COALESCE(avatar, \'captain\'),
                room_players,
                \'["ROLE_USER"]\'::json
            FROM player
        ');

        // Update users_id_seq to start after the highest player ID
        $this->addSql('SELECT setval(\'users_id_seq\', (SELECT MAX(id) FROM users))');

        // Link each player to their corresponding user (same ID)
        $this->addSql('UPDATE player SET user_id = id');

        // Set is_admin based on old ROLE_ADMIN role
        $this->addSql('UPDATE player SET is_admin = true WHERE roles::jsonb @> \'["ROLE_ADMIN"]\'::jsonb');

        // Set is_master for players with SPECTATING status
        $this->addSql('UPDATE player SET is_master = true WHERE status = \'SPECTATING\'');

        // Now add the foreign key constraint
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_98197A65A76ED395 ON player (user_id)');

        // Make user_id NOT NULL after data migration
        $this->addSql('ALTER TABLE player ALTER COLUMN user_id SET NOT NULL');

        // Remove roles column from player table (moved to user)
        $this->addSql('ALTER TABLE player DROP COLUMN roles');
    }

    public function down(Schema $schema): void
    {
        // Add roles column back to player table
        $this->addSql('ALTER TABLE player ADD roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\'');
        $this->addSql('COMMENT ON COLUMN player.roles IS \'(DC2Type:json)\'');

        // Migrate roles back from is_admin flag
        $this->addSql('UPDATE player SET roles = \'["ROLE_ADMIN"]\'::json WHERE is_admin = true');

        // Remove isAdmin and isMaster columns from player table
        $this->addSql('ALTER TABLE player DROP COLUMN is_admin');
        $this->addSql('ALTER TABLE player DROP COLUMN is_master');

        // Remove user_id column from player table
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65A76ED395');
        $this->addSql('DROP INDEX IDX_98197A65A76ED395');
        $this->addSql('ALTER TABLE player DROP COLUMN user_id');

        // Drop users table (including room_id foreign key and avatar)
        $this->addSql('ALTER TABLE users DROP CONSTRAINT FK_1483A5E954177093');
        $this->addSql('DROP INDEX IDX_1483A5E954177093');
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
    }
}
