<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221115104725 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        // this up() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SEQUENCE mission_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE player_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE SEQUENCE room_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
        $this->addSql('CREATE TABLE mission (id INT NOT NULL, user_authored_missions INT DEFAULT NULL, room_missions INT DEFAULT NULL, content VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_9067F23CB76D9DC4 ON mission (user_authored_missions)');
        $this->addSql('CREATE INDEX IDX_9067F23C941714BA ON mission (room_missions)');
        $this->addSql('CREATE TABLE player (id INT NOT NULL, room_players INT DEFAULT NULL, player_target INT DEFAULT NULL, player_killer INT DEFAULT NULL, user_assigned_mission INT DEFAULT NULL, name VARCHAR(255) NOT NULL, roles JSON NOT NULL, status VARCHAR(255) DEFAULT \'ALIVE\' NOT NULL, password VARCHAR(255) NOT NULL, PRIMARY KEY(id))');
        $this->addSql('CREATE INDEX IDX_98197A65CA6CFF46 ON player (room_players)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65D96FB922 ON player (player_target)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A654E6F28D5 ON player (player_killer)');
        $this->addSql('CREATE UNIQUE INDEX UNIQ_98197A65E7EA086E ON player (user_assigned_mission)');
        $this->addSql('CREATE TABLE room (id INT NOT NULL, code VARCHAR(5) NOT NULL, name VARCHAR(255) NOT NULL, status VARCHAR(255) DEFAULT \'PENDING\' NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, date_end TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
        $this->addSql('COMMENT ON COLUMN room.created_at IS \'(DC2Type:datetime_immutable)\'');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23CB76D9DC4 FOREIGN KEY (user_authored_missions) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE mission ADD CONSTRAINT FK_9067F23C941714BA FOREIGN KEY (room_missions) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65CA6CFF46 FOREIGN KEY (room_players) REFERENCES room (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65D96FB922 FOREIGN KEY (player_target) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A654E6F28D5 FOREIGN KEY (player_killer) REFERENCES player (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        $this->addSql('ALTER TABLE player ADD CONSTRAINT FK_98197A65E7EA086E FOREIGN KEY (user_assigned_mission) REFERENCES mission (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        $this->addSql('CREATE SCHEMA public');
        $this->addSql('DROP SEQUENCE mission_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE player_id_seq CASCADE');
        $this->addSql('DROP SEQUENCE room_id_seq CASCADE');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT FK_9067F23CB76D9DC4');
        $this->addSql('ALTER TABLE mission DROP CONSTRAINT FK_9067F23C941714BA');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65CA6CFF46');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65D96FB922');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A654E6F28D5');
        $this->addSql('ALTER TABLE player DROP CONSTRAINT FK_98197A65E7EA086E');
        $this->addSql('DROP TABLE mission');
        $this->addSql('DROP TABLE player');
        $this->addSql('DROP TABLE room');
    }
}
