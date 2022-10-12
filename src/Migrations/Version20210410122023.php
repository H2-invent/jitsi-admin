<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210410122023 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE scheduling (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, uid LONGTEXT NOT NULL, description LONGTEXT DEFAULT NULL, INDEX IDX_FD931BF554177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE scheduling_time (id INT AUTO_INCREMENT NOT NULL, scheduling_id INT NOT NULL, time DATETIME NOT NULL, INDEX IDX_6B3A7EB4157E7D92 (scheduling_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE scheduling_time_user (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, schedule_time_id INT NOT NULL, accept INT DEFAULT NULL, INDEX IDX_11E40D03A76ED395 (user_id), INDEX IDX_11E40D03D380F18A (schedule_time_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE scheduling ADD CONSTRAINT FK_FD931BF554177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
            $this->addSql('ALTER TABLE scheduling_time ADD CONSTRAINT FK_6B3A7EB4157E7D92 FOREIGN KEY (scheduling_id) REFERENCES scheduling (id)');
            $this->addSql('ALTER TABLE scheduling_time_user ADD CONSTRAINT FK_11E40D03A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE scheduling_time_user ADD CONSTRAINT FK_11E40D03D380F18A FOREIGN KEY (schedule_time_id) REFERENCES scheduling_time (id)');
            $this->addSql('ALTER TABLE rooms ADD schedule_meeting TINYINT(1) DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE scheduling_time DROP FOREIGN KEY FK_6B3A7EB4157E7D92');
            $this->addSql('ALTER TABLE scheduling_time_user DROP FOREIGN KEY FK_11E40D03D380F18A');
            $this->addSql('DROP TABLE scheduling');
            $this->addSql('DROP TABLE scheduling_time');
            $this->addSql('DROP TABLE scheduling_time_user');
            $this->addSql('ALTER TABLE rooms DROP schedule_meeting');
        }
    }
}
