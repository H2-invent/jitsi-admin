<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220316111340 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE room_status_participant (id INT AUTO_INCREMENT NOT NULL, room_status_id INT NOT NULL, entered_room_at DATETIME NOT NULL, left_room_at DATETIME DEFAULT NULL, in_room TINYINT(1) NOT NULL, INDEX IDX_18C24CFBF75EE0D4 (room_status_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE room_status_participant ADD CONSTRAINT FK_18C24CFBF75EE0D4 FOREIGN KEY (room_status_id) REFERENCES room_status (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE room_status_participant');
        }
    }
}
