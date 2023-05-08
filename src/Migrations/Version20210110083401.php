<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210110083401 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            if (!$schema->hasTable('fos_user')) {
                $this->addSql('CREATE TABLE fos_user (id INT AUTO_INCREMENT NOT NULL, email LONGTEXT NOT NULL, keycloak_id LONGTEXT DEFAULT NULL, created_at DATETIME DEFAULT NULL, username LONGTEXT DEFAULT NULL, last_login DATETIME DEFAULT NULL, first_name LONGTEXT DEFAULT NULL, last_name LONGTEXT DEFAULT NULL, register_id LONGTEXT DEFAULT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
                $this->addSql('CREATE TABLE rooms (id INT AUTO_INCREMENT NOT NULL, server_id INT NOT NULL, moderator_id INT DEFAULT NULL, name LONGTEXT NOT NULL, start DATETIME NOT NULL, enddate DATETIME DEFAULT NULL, uid LONGTEXT NOT NULL, duration DOUBLE PRECISION NOT NULL, sequence INT NOT NULL, INDEX IDX_7CA11A961844E6B7 (server_id), INDEX IDX_7CA11A96D0AFA354 (moderator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
                $this->addSql('CREATE TABLE rooms_user (rooms_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_EA64C2B48E2368AB (rooms_id), INDEX IDX_EA64C2B4A76ED395 (user_id), PRIMARY KEY(rooms_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
                $this->addSql('CREATE TABLE server (id INT AUTO_INCREMENT NOT NULL, administrator_id INT NOT NULL, url LONGTEXT NOT NULL, app_id LONGTEXT DEFAULT NULL, app_secret LONGTEXT DEFAULT NULL, INDEX IDX_5A6DD5F64B09E92C (administrator_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
                $this->addSql('CREATE TABLE server_user (server_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_613A7A91844E6B7 (server_id), INDEX IDX_613A7A9A76ED395 (user_id), PRIMARY KEY(server_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A961844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
                $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96D0AFA354 FOREIGN KEY (moderator_id) REFERENCES fos_user (id)');
                $this->addSql('ALTER TABLE rooms_user ADD CONSTRAINT FK_EA64C2B48E2368AB FOREIGN KEY (rooms_id) REFERENCES rooms (id) ON DELETE CASCADE');
                $this->addSql('ALTER TABLE rooms_user ADD CONSTRAINT FK_EA64C2B4A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
                $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F64B09E92C FOREIGN KEY (administrator_id) REFERENCES fos_user (id)');
                $this->addSql('ALTER TABLE server_user ADD CONSTRAINT FK_613A7A91844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) ON DELETE CASCADE');
                $this->addSql('ALTER TABLE server_user ADD CONSTRAINT FK_613A7A9A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
            }
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A96D0AFA354');
            $this->addSql('ALTER TABLE rooms_user DROP FOREIGN KEY FK_EA64C2B4A76ED395');
            $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F64B09E92C');
            $this->addSql('ALTER TABLE server_user DROP FOREIGN KEY FK_613A7A9A76ED395');
            $this->addSql('ALTER TABLE rooms_user DROP FOREIGN KEY FK_EA64C2B48E2368AB');
            $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A961844E6B7');
            $this->addSql('ALTER TABLE server_user DROP FOREIGN KEY FK_613A7A91844E6B7');
            $this->addSql('DROP TABLE fos_user');
            $this->addSql('DROP TABLE rooms');
            $this->addSql('DROP TABLE rooms_user');
            $this->addSql('DROP TABLE server');
            $this->addSql('DROP TABLE server_user');
        }
    }
}
