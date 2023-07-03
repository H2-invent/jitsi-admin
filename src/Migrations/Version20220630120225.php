<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220630120225 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            if (!$schema->hasTable('caller_id')) {
                $this->addSql('CREATE SEQUENCE caller_id_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE caller_room_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE caller_session_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE room_status_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE room_status_participant_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE tag_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE TABLE caller_id (id INT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, caller_session_id INT DEFAULT NULL, caller_id TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_A5626C5254177093 ON caller_id (room_id)');
                $this->addSql('CREATE INDEX IDX_A5626C52A76ED395 ON caller_id (user_id)');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_A5626C526D04C84F ON caller_id (caller_session_id)');
                $this->addSql('CREATE TABLE caller_room (id INT NOT NULL, room_id INT NOT NULL, caller_id TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_D77BF0CA54177093 ON caller_room (room_id)');
                $this->addSql('CREATE TABLE caller_session (id INT NOT NULL, lobby_waiting_user_id INT DEFAULT NULL, session_id TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, auth_ok BOOLEAN NOT NULL, caller_id TEXT DEFAULT NULL, show_name TEXT DEFAULT NULL, caller_id_verified BOOLEAN NOT NULL, force_finish BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_AD413A3FB03FB6FB ON caller_session (lobby_waiting_user_id)');
                $this->addSql('CREATE TABLE room_status (id INT NOT NULL, room_id INT NOT NULL, created BOOLEAN NOT NULL, room_created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, destroyed BOOLEAN DEFAULT NULL, destroyed_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, jitsi_room_id TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_3A4EA3DD54177093 ON room_status (room_id)');
                $this->addSql('CREATE TABLE room_status_participant (id INT NOT NULL, room_status_id INT NOT NULL, entered_room_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, left_room_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, in_room BOOLEAN NOT NULL, participant_id TEXT NOT NULL, participant_name TEXT NOT NULL, dominant_speaker_time INT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_18C24CFBF75EE0D4 ON room_status_participant (room_status_id)');
                $this->addSql('CREATE TABLE tag (id INT NOT NULL, title TEXT NOT NULL, disabled BOOLEAN NOT NULL, priority INT DEFAULT NULL, color TEXT DEFAULT NULL, background_color TEXT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE TABLE messenger_messages (id BIGSERIAL NOT NULL, body TEXT NOT NULL, headers TEXT NOT NULL, queue_name VARCHAR(190) NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, available_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, delivered_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_75EA56E0FB7336F0 ON messenger_messages (queue_name)');
                $this->addSql('CREATE INDEX IDX_75EA56E0E3BD61CE ON messenger_messages (available_at)');
                $this->addSql('CREATE INDEX IDX_75EA56E016BA31DB ON messenger_messages (delivered_at)');
                $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C5254177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C52A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C526D04C84F FOREIGN KEY (caller_session_id) REFERENCES caller_session (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE caller_room ADD CONSTRAINT FK_D77BF0CA54177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE caller_session ADD CONSTRAINT FK_AD413A3FB03FB6FB FOREIGN KEY (lobby_waiting_user_id) REFERENCES lobby_waitung_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE room_status ADD CONSTRAINT FK_3A4EA3DD54177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE room_status_participant ADD CONSTRAINT FK_18C24CFBF75EE0D4 FOREIGN KEY (room_status_id) REFERENCES room_status (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE address_group ADD indexer TEXT DEFAULT NULL');
                $this->addSql('ALTER TABLE lobby_waitung_user ADD caller_session_id INT DEFAULT NULL');
                $this->addSql('ALTER TABLE lobby_waitung_user ADD close_browser BOOLEAN DEFAULT NULL');
                $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21A6D04C84F FOREIGN KEY (caller_session_id) REFERENCES caller_session (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_6ABDB21A6D04C84F ON lobby_waitung_user (caller_session_id)');
                $this->addSql('ALTER TABLE rooms ADD tag_id INT DEFAULT NULL');
                $this->addSql('ALTER TABLE rooms ADD start_timestamp INT DEFAULT NULL');
                $this->addSql('ALTER TABLE rooms ADD end_timestamp INT DEFAULT NULL');
                $this->addSql('ALTER TABLE rooms ADD host_url TEXT DEFAULT NULL');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96BAD26311 FOREIGN KEY (tag_id) REFERENCES tag (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('CREATE INDEX IDX_7CA11A96BAD26311 ON rooms (tag_id)');
                $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F64B09E92C');
                $this->addSql('ALTER TABLE server ADD cors_header BOOLEAN DEFAULT NULL');
                $this->addSql('ALTER TABLE server ALTER administrator_id DROP NOT NULL');
                $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F64B09E92C FOREIGN KEY (administrator_id) REFERENCES fos_user (id) ON DELETE SET NULL NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE userroomsattributes DROP CONSTRAINT FK_F98B4CE454177093');
                $this->addSql('ALTER TABLE userroomsattributes ADD CONSTRAINT FK_F98B4CE454177093 FOREIGN KEY (room_id) REFERENCES rooms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
//            $this->addSql('CREATE SCHEMA public');
            $this->addSql('ALTER TABLE caller_id DROP CONSTRAINT FK_A5626C526D04C84F');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP CONSTRAINT FK_6ABDB21A6D04C84F');
            $this->addSql('ALTER TABLE room_status_participant DROP CONSTRAINT FK_18C24CFBF75EE0D4');
            $this->addSql('ALTER TABLE rooms DROP CONSTRAINT FK_7CA11A96BAD26311');
            $this->addSql('DROP SEQUENCE caller_id_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE caller_room_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE caller_session_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE room_status_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE room_status_participant_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE tag_id_seq CASCADE');
            $this->addSql('DROP TABLE caller_id');
            $this->addSql('DROP TABLE caller_room');
            $this->addSql('DROP TABLE caller_session');
            $this->addSql('DROP TABLE room_status');
            $this->addSql('DROP TABLE room_status_participant');
            $this->addSql('DROP TABLE tag');
            $this->addSql('DROP TABLE messenger_messages');
            $this->addSql('ALTER TABLE address_group DROP indexer');
            $this->addSql('ALTER TABLE server DROP CONSTRAINT fk_5a6dd5f64b09e92c');
            $this->addSql('ALTER TABLE server DROP cors_header');
            $this->addSql('ALTER TABLE server ALTER administrator_id SET NOT NULL');
            $this->addSql('ALTER TABLE server ADD CONSTRAINT fk_5a6dd5f64b09e92c FOREIGN KEY (administrator_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('DROP INDEX IDX_7CA11A96BAD26311');
            $this->addSql('ALTER TABLE rooms DROP tag_id');
            $this->addSql('ALTER TABLE rooms DROP start_timestamp');
            $this->addSql('ALTER TABLE rooms DROP end_timestamp');
            $this->addSql('ALTER TABLE rooms DROP host_url');
            $this->addSql('DROP INDEX UNIQ_6ABDB21A6D04C84F');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP caller_session_id');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP close_browser');
            $this->addSql('ALTER TABLE userRoomsAttributes DROP CONSTRAINT fk_f98b4ce454177093');
            $this->addSql('ALTER TABLE userRoomsAttributes ADD CONSTRAINT fk_f98b4ce454177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    }
}
