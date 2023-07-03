<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221104102834 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms ADD creator_id INT DEFAULT NULL');
            $this->addSql('UPDATE rooms SET creator_id=moderator_id WHERE creator_id IS NULL ');
            $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A9661220EA6 FOREIGN KEY (creator_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('CREATE INDEX IDX_7CA11A9661220EA6 ON rooms (creator_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms DROP CONSTRAINT FK_7CA11A9661220EA6');
            $this->addSql('DROP INDEX IDX_7CA11A9661220EA6');
            $this->addSql('ALTER TABLE rooms DROP creator_id');
        }
    }
}
