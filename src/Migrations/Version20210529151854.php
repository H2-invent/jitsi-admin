<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210529151854 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE `repeat` ADD prototyp_id INT NOT NULL');
            $this->addSql('ALTER TABLE `repeat` ADD CONSTRAINT FK_A857B3C027692A7E FOREIGN KEY (prototyp_id) REFERENCES rooms (id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A857B3C027692A7E ON `repeat` (prototyp_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE `repeat` DROP FOREIGN KEY FK_A857B3C027692A7E');
            $this->addSql('DROP INDEX UNIQ_A857B3C027692A7E ON `repeat`');
            $this->addSql('ALTER TABLE `repeat` DROP prototyp_id');
        }
    }
}
