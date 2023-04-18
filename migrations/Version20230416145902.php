<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20230416145902 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('DROP SEQUENCE room_id_seq CASCADE');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT FK_9067F23C941714BA');
        $this->addSql('ALTER TABLE mission ALTER room_missions TYPE VARCHAR(5)');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65CA6CFF46');
        $this->addSql('ALTER TABLE player ALTER room_players TYPE VARCHAR(5)');
//        $this->addSql('DROP INDEX "primary"');
        $this->addSql('ALTER TABLE room DROP id');
        $this->addSql('ALTER TABLE room ADD PRIMARY KEY (code)');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C941714BA FOREIGN KEY (room_missions) REFERENCES room (code) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65CA6CFF46 FOREIGN KEY (room_players) REFERENCES room (code) NOT DEFERRABLE INITIALLY IMMEDIATE');

    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('CREATE SEQUENCE room_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT fk_9067f23c941714ba');
        $this->addSql('ALTER TABLE mission ALTER room_missions TYPE INT');
        $this->addSql('ALTER TABLE mission ALTER room_missions TYPE INT');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT fk_9067f23c941714ba FOREIGN KEY (room_missions) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('DROP INDEX room_pkey');
        $this->addSql('ALTER TABLE room ADD id INT NOT NULL');
        $this->addSql('ALTER TABLE room ADD PRIMARY KEY (id)');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT fk_98197a65ca6cff46');
        $this->addSql('ALTER TABLE player ALTER room_players TYPE INT');
        $this->addSql('ALTER TABLE player ALTER room_players TYPE INT');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT fk_98197a65ca6cff46 FOREIGN KEY (room_players) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }
}
