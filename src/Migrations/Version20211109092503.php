<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211109092503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE lobby_waitung_user (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, room_id INT NOT NULL, created_at DATETIME NOT NULL, INDEX IDX_6ABDB21AA76ED395 (user_id), INDEX IDX_6ABDB21A54177093 (room_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21AA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21A54177093 FOREIGN KEY (room_id) REFERENCES rooms (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE lobby_waitung_user');
        }
    }
}
