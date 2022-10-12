<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220328065334 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE caller_id (id INT AUTO_INCREMENT NOT NULL, room_id INT NOT NULL, user_id INT NOT NULL, caller_id LONGTEXT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_A5626C5254177093 (room_id), INDEX IDX_A5626C52A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C5254177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
            $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C52A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE caller_id');
        }
    }
}
