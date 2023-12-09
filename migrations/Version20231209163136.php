<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20231209163136 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP CONSTRAINT fk_98197a65d96fb922');
        $this->addSql('DROP INDEX uniq_98197a65d96fb922');
        $this->addSql('ALTER TABLE player DROP player_target');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE player ADD player_target INT DEFAULT NULL');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT fk_98197a65d96fb922 FOREIGN KEY (player_target) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_98197a65d96fb922 ON player (player_target)');
    }
}
