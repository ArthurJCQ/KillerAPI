<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251030102148 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP CONSTRAINT fk_98197a654e6f28d5');
        $this->addSql('DROP INDEX uniq_98197a654e6f28d5');
        $this->addSql('ALTER TABLE player RENAME COLUMN player_killer TO player_target');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65D96FB922 FOREIGN KEY (player_target) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65D96FB922 ON player (player_target)');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65D96FB922');
        $this->addSql('DROP INDEX UNIQ_98197A65D96FB922');
        $this->addSql('ALTER TABLE player RENAME COLUMN player_target TO player_killer');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT fk_98197a654e6f28d5 FOREIGN KEY (player_killer) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('CREATE UNIQUE INDEX uniq_98197a654e6f28d5 ON player (player_killer)');
    }
}
