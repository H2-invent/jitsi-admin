<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220709114814 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server ADD server_background_image_id INT DEFAULT NULL, ADD updated_at DATETIME DEFAULT NULL');
            $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F62C3C2138 FOREIGN KEY (server_background_image_id) REFERENCES documents (id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_5A6DD5F62C3C2138 ON server (server_background_image_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F62C3C2138');
            $this->addSql('DROP INDEX UNIQ_5A6DD5F62C3C2138 ON server');
            $this->addSql('ALTER TABLE server DROP server_background_image_id, DROP updated_at');
        }
    }
}
