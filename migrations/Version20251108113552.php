<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Migration: Add User entity and link Player to User and Room
 */
final class Version20251108113552 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Create users table and add user_id to player table, remove roles from player';
    }

    public function up(Schema $schema): void
    {
        // Create users table
        $this->addSql('CREATE SEQUENCE users_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE users (
            id INT NOT NULL,
            email VARCHAR(255) DEFAULT NULL,
            default_name VARCHAR(255) NOT NULL,
            roles JSON NOT NULL,
            google_id VARCHAR(255) DEFAULT NULL,
            apple_id VARCHAR(255) DEFAULT NULL,
            PRIMARY KEY(id)
        )');
        $this->addSql('CREATE INDEX IDX_1483A5E9E7927C74 ON users (email)');
        $this->addSql('CREATE INDEX IDX_1483A5E9772E836A ON users (google_id)');
        $this->addSql('CREATE INDEX IDX_1483A5E96B01BC5B ON users (apple_id)');
        $this->addSql('COMMENT ON COLUMN users.roles IS \'(DC2Type:json)\'');

        // Add user_id column to player table
        $this->addSql('ALTER TABLE player ADD user_id INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65A76ED395 FOREIGN KEY (user_id) REFERENCES users (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE INDEX IDX_98197A65A76ED395 ON player (user_id)');

        // Remove roles column from player table
        $this->addSql('ALTER TABLE player DROP COLUMN roles');
    }

    public function down(Schema $schema): void
    {
        // Add roles column back to player table
        $this->addSql('ALTER TABLE player ADD roles JSON NOT NULL DEFAULT \'["ROLE_USER"]\'');
        $this->addSql('COMMENT ON COLUMN player.roles IS \'(DC2Type:json)\'');

        // Remove user_id column from player table
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65A76ED395');
        $this->addSql('DROP INDEX IDX_98197A65A76ED395');
        $this->addSql('ALTER TABLE player DROP COLUMN user_id');

        // Drop users table
        $this->addSql('DROP TABLE users');
        $this->addSql('DROP SEQUENCE users_id_seq CASCADE');
    }
}
