<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220316083227 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms ADD room_status_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE rooms ADD CONSTRAINT FK_7CA11A96F75EE0D4 FOREIGN KEY (room_status_id) REFERENCES room_status (id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_7CA11A96F75EE0D4 ON rooms (room_status_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE rooms DROP FOREIGN KEY FK_7CA11A96F75EE0D4');
            $this->addSql('DROP INDEX UNIQ_7CA11A96F75EE0D4 ON rooms');
            $this->addSql('ALTER TABLE rooms DROP room_status_id');
        }
    }
}
