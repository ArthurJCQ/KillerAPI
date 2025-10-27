<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251027000000 extends AbstractMigration
{
    public function getDescription(): string
    {
        return 'Add points and mission switch tracking to player';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player ADD points INT DEFAULT 0 NOT NULL');
        $this->addSql('ALTER TABLE player ADD mission_switch_used BOOLEAN DEFAULT false NOT NULL');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE player DROP points');
        $this->addSql('ALTER TABLE player DROP mission_switch_used');
    }
}
