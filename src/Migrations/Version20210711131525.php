<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210711131525 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE fos_user ADD my_own_room_server_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE fos_user ADD CONSTRAINT FK_957A64796341294B FOREIGN KEY (my_own_room_server_id) REFERENCES server (id)');
            $this->addSql('CREATE INDEX IDX_957A64796341294B ON fos_user (my_own_room_server_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE fos_user DROP FOREIGN KEY FK_957A64796341294B');
            $this->addSql('DROP INDEX IDX_957A64796341294B ON fos_user');
            $this->addSql('ALTER TABLE fos_user DROP my_own_room_server_id');
        }
    }
}
