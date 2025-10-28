<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20251028122507 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE TABLE room_secondary_missions (room_id VARCHAR(5) NOT NULL, mission_id INT NOT NULL, PRIMARY KEY(room_id, mission_id))');
        $this->addSql('CREATE INDEX IDX_64D75A2354177093 ON room_secondary_missions (room_id)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_64D75A23BE6CAE90 ON room_secondary_missions (mission_id)');
        $this->addSql('ALTER TABLE room_secondary_missions ADD CONSTRAINT FK_64D75A2354177093 FOREIGN KEY (room_id) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE room_secondary_missions ADD CONSTRAINT FK_64D75A23BE6CAE90 FOREIGN KEY (mission_id) REFERENCES mission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE room_secondary_missions DROP CONSTRAINT FK_64D75A2354177093');
        $this->addSql('ALTER TABLE room_secondary_missions DROP CONSTRAINT FK_64D75A23BE6CAE90');
        $this->addSql('DROP TABLE room_secondary_missions');
    }
}
