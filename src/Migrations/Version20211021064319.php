<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20211021064319 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE user_rooms (user_id INT NOT NULL, rooms_id INT NOT NULL, INDEX IDX_9E63E1CEA76ED395 (user_id), INDEX IDX_9E63E1CE8E2368AB (rooms_id), PRIMARY KEY(user_id, rooms_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE user_rooms ADD CONSTRAINT FK_9E63E1CEA76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE user_rooms ADD CONSTRAINT FK_9E63E1CE8E2368AB FOREIGN KEY (rooms_id) REFERENCES rooms (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE user_rooms');
        }
    }
}
