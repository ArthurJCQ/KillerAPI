<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230325155717 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT FK_9067F23C941714BA');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C941714BA FOREIGN KEY (room_missions) REFERENCES room (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT fk_9067f23c941714ba');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT fk_9067f23c941714ba FOREIGN KEY (room_missions) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
