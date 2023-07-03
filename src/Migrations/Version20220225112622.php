<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Cron\Cron;
use Cron\CronBundle\Entity\CronJob;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220225112622 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {

        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            if (!$schema->hasTable('fos_user')) {
                // this up() migration is auto-generated, please modify it to your needs
                $this->addSql('CREATE SEQUENCE address_group_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE api_keys_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE cron_job_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE cron_report_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE documents_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE fos_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE keycloak_groups_to_servers_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE ldap_user_properties_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE license_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE lobby_waitung_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE notification_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE "repeat_id_seq" INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE rooms_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE scheduling_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE scheduling_time_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE scheduling_time_user_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE server_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE subscriber_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE userRoomsAttributes_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE SEQUENCE waitinglist_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
                $this->addSql('CREATE TABLE address_group (id INT NOT NULL, leader_id INT NOT NULL, name TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_5A6533E573154ED4 ON address_group (leader_id)');
                $this->addSql('COMMENT ON COLUMN address_group.created_at IS \'(DC2Type:datetime_immutable)\'');
                $this->addSql('COMMENT ON COLUMN address_group.updated_at IS \'(DC2Type:datetime_immutable)\'');
                $this->addSql('CREATE TABLE address_group_user (address_group_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(address_group_id, user_id))');
                $this->addSql('CREATE INDEX IDX_DC5405A7DB27C6 ON address_group_user (address_group_id)');
                $this->addSql('CREATE INDEX IDX_DC5405A7A76ED395 ON address_group_user (user_id)');
                $this->addSql('CREATE TABLE api_keys (id INT NOT NULL, client_id TEXT NOT NULL, client_secret TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE TABLE cron_job (id INT NOT NULL, name VARCHAR(191) NOT NULL, command VARCHAR(1024) NOT NULL, schedule VARCHAR(191) NOT NULL, description VARCHAR(191) NOT NULL, enabled BOOLEAN NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE UNIQUE INDEX un_name ON cron_job (name)');
                $this->addSql('CREATE TABLE cron_report (id INT NOT NULL, job_id INT DEFAULT NULL, run_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, run_time DOUBLE PRECISION NOT NULL, exit_code INT NOT NULL, output TEXT NOT NULL, error TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_B6C6A7F5BE04EA9 ON cron_report (job_id)');
                $this->addSql('CREATE TABLE documents (id INT NOT NULL, document_file_name VARCHAR(255) DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE TABLE fos_user (id INT NOT NULL, my_own_room_server_id INT DEFAULT NULL, profile_picture_id INT DEFAULT NULL, email TEXT NOT NULL, keycloak_id TEXT DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, username TEXT DEFAULT NULL, last_login TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, first_name TEXT DEFAULT NULL, last_name TEXT DEFAULT NULL, register_id TEXT DEFAULT NULL, keycloakGroup TEXT DEFAULT NULL, uid TEXT DEFAULT NULL, own_room_uid TEXT DEFAULT NULL, time_zone VARCHAR(255) DEFAULT NULL, spezial_properties TEXT DEFAULT NULL, indexer TEXT DEFAULT NULL, second_email TEXT DEFAULT NULL, updated_at TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_957A64796341294B ON fos_user (my_own_room_server_id)');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_957A6479292E8AE2 ON fos_user (profile_picture_id)');
                $this->addSql('COMMENT ON COLUMN fos_user.keycloakGroup IS \'(DC2Type:array)\'');
                $this->addSql('COMMENT ON COLUMN fos_user.spezial_properties IS \'(DC2Type:array)\'');
                $this->addSql('CREATE TABLE user_user (user_source INT NOT NULL, user_target INT NOT NULL, PRIMARY KEY(user_source, user_target))');
                $this->addSql('CREATE INDEX IDX_F7129A803AD8644E ON user_user (user_source)');
                $this->addSql('CREATE INDEX IDX_F7129A80233D34C1 ON user_user (user_target)');
                $this->addSql('CREATE TABLE user_rooms (user_id INT NOT NULL, rooms_id INT NOT NULL, PRIMARY KEY(user_id, rooms_id))');
                $this->addSql('CREATE INDEX IDX_9E63E1CEA76ED395 ON user_rooms (user_id)');
                $this->addSql('CREATE INDEX IDX_9E63E1CE8E2368AB ON user_rooms (rooms_id)');
                $this->addSql('CREATE TABLE keycloak_groups_to_servers (id INT NOT NULL, server_id INT NOT NULL, keycloak_group TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_A15849ED1844E6B7 ON keycloak_groups_to_servers (server_id)');
                $this->addSql('CREATE TABLE ldap_user_properties (id INT NOT NULL, user_id INT NOT NULL, ldap_host TEXT NOT NULL, ldap_dn TEXT NOT NULL, rdn TEXT DEFAULT NULL, ldap_number TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_ACEA2AF5A76ED395 ON ldap_user_properties (user_id)');
                $this->addSql('CREATE TABLE license (id INT NOT NULL, license_key TEXT NOT NULL, license TEXT NOT NULL, valid_until TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, url TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE TABLE lobby_waitung_user (id INT NOT NULL, user_id INT DEFAULT NULL, room_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, uid TEXT NOT NULL, type VARCHAR(5) NOT NULL, show_name TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_6ABDB21AA76ED395 ON lobby_waitung_user (user_id)');
                $this->addSql('CREATE INDEX IDX_6ABDB21A54177093 ON lobby_waitung_user (room_id)');
                $this->addSql('CREATE TABLE notification (id INT NOT NULL, user_id INT NOT NULL, title TEXT NOT NULL, text TEXT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, url TEXT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_BF5476CAA76ED395 ON notification (user_id)');
                $this->addSql('CREATE TABLE "repeat" (id INT NOT NULL, prototyp_id INT DEFAULT NULL, repetation INT DEFAULT NULL, repeat_until TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, weekday TEXT NOT NULL, weeks INT DEFAULT NULL, months INT DEFAULT NULL, days INT DEFAULT NULL, repeat_type INT NOT NULL, repeater_days INT DEFAULT NULL, repeater_weeks INT DEFAULT NULL, repeat_montly INT DEFAULT NULL, repeat_yearly INT DEFAULT NULL, start_date TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, repat_month_relativ_number INT DEFAULT NULL, repat_month_relativ_weekday INT DEFAULT NULL, repeat_yearly_relative_number INT DEFAULT NULL, repeat_yearly_relative_month INT DEFAULT NULL, repeat_yearly_relative_weekday INT DEFAULT NULL, repeat_monthly_relative_how_often INT DEFAULT NULL, repeat_yearly_relative_how_often INT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE UNIQUE INDEX UNIQ_A857B3C027692A7E ON "repeat" (prototyp_id)');
                $this->addSql('COMMENT ON COLUMN "repeat".weekday IS \'(DC2Type:array)\'');
                $this->addSql('CREATE TABLE repeat_user (repeat_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(repeat_id, user_id))');
                $this->addSql('CREATE INDEX IDX_3949A129CD096AF4 ON repeat_user (repeat_id)');
                $this->addSql('CREATE INDEX IDX_3949A129A76ED395 ON repeat_user (user_id)');
                $this->addSql('CREATE TABLE rooms (id INT NOT NULL, server_id INT NOT NULL, moderator_id INT DEFAULT NULL, repeater_id INT DEFAULT NULL, name TEXT NOT NULL, start TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, enddate TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, uid TEXT NOT NULL, duration DOUBLE PRECISION NOT NULL, sequence INT NOT NULL, uid_real TEXT DEFAULT NULL, only_registered_users BOOLEAN NOT NULL, agenda TEXT DEFAULT NULL, dissallow_screenshare_global BOOLEAN DEFAULT NULL, dissallow_private_message BOOLEAN DEFAULT NULL, public BOOLEAN DEFAULT NULL, show_room_on_joinpage BOOLEAN DEFAULT NULL, uid_participant TEXT DEFAULT NULL, uid_moderator TEXT DEFAULT NULL, max_participants INT DEFAULT NULL, schedule_meeting BOOLEAN DEFAULT NULL, waitinglist BOOLEAN DEFAULT NULL, repeater_removed BOOLEAN DEFAULT NULL, persistant_room BOOLEAN DEFAULT NULL, slug TEXT DEFAULT NULL, total_open_rooms BOOLEAN DEFAULT NULL, total_open_rooms_open_time INT DEFAULT NULL, time_zone VARCHAR(255) DEFAULT NULL, start_utc TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, end_date_utc TIMESTAMP(0) WITHOUT TIME ZONE DEFAULT NULL, lobby BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_7CA11A961844E6B7 ON rooms (server_id)');
                $this->addSql('CREATE INDEX IDX_7CA11A96D0AFA354 ON rooms (moderator_id)');
                $this->addSql('CREATE INDEX IDX_7CA11A9626397C6E ON rooms (repeater_id)');
                $this->addSql('CREATE TABLE rooms_user (rooms_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(rooms_id, user_id))');
                $this->addSql('CREATE INDEX IDX_EA64C2B48E2368AB ON rooms_user (rooms_id)');
                $this->addSql('CREATE INDEX IDX_EA64C2B4A76ED395 ON rooms_user (user_id)');
                $this->addSql('CREATE TABLE prototype_users (rooms_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(rooms_id, user_id))');
                $this->addSql('CREATE INDEX IDX_EE6833D58E2368AB ON prototype_users (rooms_id)');
                $this->addSql('CREATE INDEX IDX_EE6833D5A76ED395 ON prototype_users (user_id)');
                $this->addSql('CREATE TABLE scheduling (id INT NOT NULL, room_id INT NOT NULL, uid TEXT NOT NULL, description TEXT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_FD931BF554177093 ON scheduling (room_id)');
                $this->addSql('CREATE TABLE scheduling_time (id INT NOT NULL, scheduling_id INT NOT NULL, time TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_6B3A7EB4157E7D92 ON scheduling_time (scheduling_id)');
                $this->addSql('CREATE TABLE scheduling_time_user (id INT NOT NULL, user_id INT NOT NULL, schedule_time_id INT NOT NULL, accept INT DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_11E40D03A76ED395 ON scheduling_time_user (user_id)');
                $this->addSql('CREATE INDEX IDX_11E40D03D380F18A ON scheduling_time_user (schedule_time_id)');
                $this->addSql('CREATE TABLE server (id INT NOT NULL, administrator_id INT NOT NULL, url TEXT NOT NULL, app_id TEXT DEFAULT NULL, app_secret TEXT DEFAULT NULL, logo_url TEXT DEFAULT NULL, smtp_host TEXT DEFAULT NULL, smtp_port INT DEFAULT NULL, smtp_password TEXT DEFAULT NULL, smtp_username TEXT DEFAULT NULL, smtp_encryption TEXT DEFAULT NULL, smtp_email TEXT DEFAULT NULL, smtp_sender_name TEXT DEFAULT NULL, slug TEXT NOT NULL, privacy_policy TEXT DEFAULT NULL, license_key TEXT DEFAULT NULL, api_key TEXT DEFAULT NULL, static_background_color VARCHAR(7) DEFAULT NULL, show_static_background_color BOOLEAN DEFAULT NULL, feature_enable_by_jwt BOOLEAN DEFAULT NULL, server_email_header TEXT DEFAULT NULL, server_email_body TEXT DEFAULT NULL, jwt_moderator_position INT NOT NULL, server_name TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_5A6DD5F64B09E92C ON server (administrator_id)');
                $this->addSql('CREATE TABLE server_user (server_id INT NOT NULL, user_id INT NOT NULL, PRIMARY KEY(server_id, user_id))');
                $this->addSql('CREATE INDEX IDX_613A7A91844E6B7 ON server_user (server_id)');
                $this->addSql('CREATE INDEX IDX_613A7A9A76ED395 ON server_user (user_id)');
                $this->addSql('CREATE TABLE subscriber (id INT NOT NULL, user_id INT NOT NULL, room_id INT NOT NULL, uid TEXT NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_AD005B69A76ED395 ON subscriber (user_id)');
                $this->addSql('CREATE INDEX IDX_AD005B6954177093 ON subscriber (room_id)');
                $this->addSql('CREATE TABLE userRoomsAttributes (id INT NOT NULL, user_id INT NOT NULL, room_id INT NOT NULL, share_display BOOLEAN DEFAULT NULL, moderator BOOLEAN DEFAULT NULL, private_message BOOLEAN DEFAULT NULL, lobby_moderator BOOLEAN DEFAULT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_F98B4CE4A76ED395 ON userRoomsAttributes (user_id)');
                $this->addSql('CREATE INDEX IDX_F98B4CE454177093 ON userRoomsAttributes (room_id)');
                $this->addSql('CREATE TABLE waitinglist (id INT NOT NULL, user_id INT NOT NULL, room_id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
                $this->addSql('CREATE INDEX IDX_382FDA02A76ED395 ON waitinglist (user_id)');
                $this->addSql('CREATE INDEX IDX_382FDA0254177093 ON waitinglist (room_id)');
                $this->addSql('ALTER TABLE address_group ADD CONSTRAINT FK_5A6533E573154ED4 FOREIGN KEY (leader_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE address_group_user ADD CONSTRAINT FK_DC5405A7DB27C6 FOREIGN KEY (address_group_id) REFERENCES address_group (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE address_group_user ADD CONSTRAINT FK_DC5405A7A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE cron_report ADD CONSTRAINT FK_B6C6A7F5BE04EA9 FOREIGN KEY (job_id) REFERENCES cron_job (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A64796341294B FOREIGN KEY (my_own_room_server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A6479292E8AE2 FOREIGN KEY (profile_picture_id) REFERENCES documents (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A803AD8644E FOREIGN KEY (user_source) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE user_user ADD CONSTRAINT FK_F7129A80233D34C1 FOREIGN KEY (user_target) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE user_rooms ADD CONSTRAINT FK_9E63E1CEA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE user_rooms ADD CONSTRAINT FK_9E63E1CE8E2368AB FOREIGN KEY (rooms_id) REFERENCES rooms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE keycloak_groups_to_servers ADD CONSTRAINT FK_A15849ED1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE ldap_user_properties ADD CONSTRAINT FK_ACEA2AF5A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21AA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21A54177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE notification ADD CONSTRAINT FK_BF5476CAA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE "repeat" ADD CONSTRAINT FK_A857B3C027692A7E FOREIGN KEY (prototyp_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE repeat_user ADD CONSTRAINT FK_3949A129CD096AF4 FOREIGN KEY (repeat_id) REFERENCES "repeat" (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE repeat_user ADD CONSTRAINT FK_3949A129A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A961844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96D0AFA354 FOREIGN KEY (moderator_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A9626397C6E FOREIGN KEY (repeater_id) REFERENCES "repeat" (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE rooms_user ADD CONSTRAINT FK_EA64C2B48E2368AB FOREIGN KEY (rooms_id) REFERENCES rooms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE rooms_user ADD CONSTRAINT FK_EA64C2B4A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE prototype_users ADD CONSTRAINT FK_EE6833D58E2368AB FOREIGN KEY (rooms_id) REFERENCES rooms (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE prototype_users ADD CONSTRAINT FK_EE6833D5A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE scheduling ADD CONSTRAINT FK_FD931BF554177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE scheduling_time ADD CONSTRAINT FK_6B3A7EB4157E7D92 FOREIGN KEY (scheduling_id) REFERENCES scheduling (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE scheduling_time_user ADD CONSTRAINT FK_11E40D03A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE scheduling_time_user ADD CONSTRAINT FK_11E40D03D380F18A FOREIGN KEY (schedule_time_id) REFERENCES scheduling_time (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F64B09E92C FOREIGN KEY (administrator_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE server_user ADD CONSTRAINT FK_613A7A91844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE server_user ADD CONSTRAINT FK_613A7A9A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE subscriber ADD CONSTRAINT FK_AD005B69A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE subscriber ADD CONSTRAINT FK_AD005B6954177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE userRoomsAttributes ADD CONSTRAINT FK_F98B4CE4A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE userRoomsAttributes ADD CONSTRAINT FK_F98B4CE454177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE waitinglist ADD CONSTRAINT FK_382FDA02A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
                $this->addSql('ALTER TABLE waitinglist ADD CONSTRAINT FK_382FDA0254177093 FOREIGN KEY (room_id) REFERENCES rooms (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            }
        }
    }


    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
//            $this->addSql('CREATE SCHEMA public');
            $this->addSql('ALTER TABLE address_group_user DROP CONSTRAINT FK_DC5405A7DB27C6');
            $this->addSql('ALTER TABLE cron_report DROP CONSTRAINT FK_B6C6A7F5BE04EA9');
            $this->addSql('ALTER TABLE fos_user DROP CONSTRAINT FK_957A6479292E8AE2');
            $this->addSql('ALTER TABLE address_group DROP CONSTRAINT FK_5A6533E573154ED4');
            $this->addSql('ALTER TABLE address_group_user DROP CONSTRAINT FK_DC5405A7A76ED395');
            $this->addSql('ALTER TABLE user_user DROP CONSTRAINT FK_F7129A803AD8644E');
            $this->addSql('ALTER TABLE user_user DROP CONSTRAINT FK_F7129A80233D34C1');
            $this->addSql('ALTER TABLE user_rooms DROP CONSTRAINT FK_9E63E1CEA76ED395');
            $this->addSql('ALTER TABLE ldap_user_properties DROP CONSTRAINT FK_ACEA2AF5A76ED395');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP CONSTRAINT FK_6ABDB21AA76ED395');
            $this->addSql('ALTER TABLE notification DROP CONSTRAINT FK_BF5476CAA76ED395');
            $this->addSql('ALTER TABLE repeat_user DROP CONSTRAINT FK_3949A129A76ED395');
            $this->addSql('ALTER TABLE rooms DROP CONSTRAINT FK_7CA11A96D0AFA354');
            $this->addSql('ALTER TABLE rooms_user DROP CONSTRAINT FK_EA64C2B4A76ED395');
            $this->addSql('ALTER TABLE prototype_users DROP CONSTRAINT FK_EE6833D5A76ED395');
            $this->addSql('ALTER TABLE scheduling_time_user DROP CONSTRAINT FK_11E40D03A76ED395');
            $this->addSql('ALTER TABLE server DROP CONSTRAINT FK_5A6DD5F64B09E92C');
            $this->addSql('ALTER TABLE server_user DROP CONSTRAINT FK_613A7A9A76ED395');
            $this->addSql('ALTER TABLE subscriber DROP CONSTRAINT FK_AD005B69A76ED395');
            $this->addSql('ALTER TABLE userRoomsAttributes DROP CONSTRAINT FK_F98B4CE4A76ED395');
            $this->addSql('ALTER TABLE waitinglist DROP CONSTRAINT FK_382FDA02A76ED395');
            $this->addSql('ALTER TABLE repeat_user DROP CONSTRAINT FK_3949A129CD096AF4');
            $this->addSql('ALTER TABLE rooms DROP CONSTRAINT FK_7CA11A9626397C6E');
            $this->addSql('ALTER TABLE user_rooms DROP CONSTRAINT FK_9E63E1CE8E2368AB');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP CONSTRAINT FK_6ABDB21A54177093');
            $this->addSql('ALTER TABLE "repeat" DROP CONSTRAINT FK_A857B3C027692A7E');
            $this->addSql('ALTER TABLE rooms_user DROP CONSTRAINT FK_EA64C2B48E2368AB');
            $this->addSql('ALTER TABLE prototype_users DROP CONSTRAINT FK_EE6833D58E2368AB');
            $this->addSql('ALTER TABLE scheduling DROP CONSTRAINT FK_FD931BF554177093');
            $this->addSql('ALTER TABLE subscriber DROP CONSTRAINT FK_AD005B6954177093');
            $this->addSql('ALTER TABLE userRoomsAttributes DROP CONSTRAINT FK_F98B4CE454177093');
            $this->addSql('ALTER TABLE waitinglist DROP CONSTRAINT FK_382FDA0254177093');
            $this->addSql('ALTER TABLE scheduling_time DROP CONSTRAINT FK_6B3A7EB4157E7D92');
            $this->addSql('ALTER TABLE scheduling_time_user DROP CONSTRAINT FK_11E40D03D380F18A');
            $this->addSql('ALTER TABLE fos_user DROP CONSTRAINT FK_957A64796341294B');
            $this->addSql('ALTER TABLE keycloak_groups_to_servers DROP CONSTRAINT FK_A15849ED1844E6B7');
            $this->addSql('ALTER TABLE rooms DROP CONSTRAINT FK_7CA11A961844E6B7');
            $this->addSql('ALTER TABLE server_user DROP CONSTRAINT FK_613A7A91844E6B7');
            $this->addSql('DROP SEQUENCE address_group_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE api_keys_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE cron_job_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE cron_report_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE documents_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE fos_user_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE keycloak_groups_to_servers_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE ldap_user_properties_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE license_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE lobby_waitung_user_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE notification_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE "repeat_id_seq" CASCADE');
            $this->addSql('DROP SEQUENCE rooms_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE scheduling_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE scheduling_time_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE scheduling_time_user_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE server_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE subscriber_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE userRoomsAttributes_id_seq CASCADE');
            $this->addSql('DROP SEQUENCE waitinglist_id_seq CASCADE');
            $this->addSql('DROP TABLE address_group');
            $this->addSql('DROP TABLE address_group_user');
            $this->addSql('DROP TABLE api_keys');
            $this->addSql('DROP TABLE cron_job');
            $this->addSql('DROP TABLE cron_report');
            $this->addSql('DROP TABLE documents');
            $this->addSql('DROP TABLE fos_user');
            $this->addSql('DROP TABLE user_user');
            $this->addSql('DROP TABLE user_rooms');
            $this->addSql('DROP TABLE keycloak_groups_to_servers');
            $this->addSql('DROP TABLE ldap_user_properties');
            $this->addSql('DROP TABLE license');
            $this->addSql('DROP TABLE lobby_waitung_user');
            $this->addSql('DROP TABLE notification');
            $this->addSql('DROP TABLE "repeat"');
            $this->addSql('DROP TABLE repeat_user');
            $this->addSql('DROP TABLE rooms');
            $this->addSql('DROP TABLE rooms_user');
            $this->addSql('DROP TABLE prototype_users');
            $this->addSql('DROP TABLE scheduling');
            $this->addSql('DROP TABLE scheduling_time');
            $this->addSql('DROP TABLE scheduling_time_user');
            $this->addSql('DROP TABLE server');
            $this->addSql('DROP TABLE server_user');
            $this->addSql('DROP TABLE subscriber');
            $this->addSql('DROP TABLE userRoomsAttributes');
            $this->addSql('DROP TABLE waitinglist');
        }
    }

    public function postUp(Schema $schema): void
    {
        parent::postUp($schema); // TODO: Change the autogenerated stub
//
//        if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:cron:sendReminder%'")->fetchOne() == 0) {
//            $this->connection->executeQuery("INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ('sendReminder','app:cron:sendReminder','*/10 * * * *','send reminder','1')");
//        }
//
//
//        if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:index:user%'")->fetchOne() == 0) {
//
//            $this->connection->executeQuery('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("sendReminder","app:cron:sendReminder","*/10 * * * *","send reminder","1")');
//
//        }
//        if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%app:lobby:cleanUp%'")->fetchOne() == 0) {
//            $this->connection->executeQuery('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("sendReminder","app:cron:sendReminder","*/10 * * * *","send reminder","1")');
//
//            $this->connection->insert('cron_job', array(
//                    'name' => "cleanLobby",
//                    'command' => "app:lobby:cleanUp  2",
//                    'schedule' => "0 * * * *",
//                    'description' => "Clean up the Lobby",
//                    'enabled' => "1"
//                )
//            );
//        }
//        if ($this->connection->executeQuery("SELECT * from cron_job where command  like '%cron:reports:truncate%'")->fetchOne() !== 0) {
//            $this->connection->executeQuery('INSERT INTO cron_job (name, command, schedule,description, enabled) VALUES ("sendReminder","app:cron:sendReminder","*/10 * * * *","send reminder","1")');
//
//
//            $this->connection->insert('cron_job', array(
//                    'name' => "cleanReports",
//                    'command' => "cron:reports:truncate 10",
//                    'schedule' => "0 * * * *",
//                    'description' => "Clean up Repots",
//                    'enabled' => "1"
//                )
//            );
//        }
    }
}
