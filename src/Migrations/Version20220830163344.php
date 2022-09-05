<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220830163344 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server ADD star_url TEXT DEFAULT NULL');
            $this->addSql('ALTER TABLE server ADD star_server_id INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        // this down() migration is auto-generated, please modify it to your needs
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            $this->addSql('ALTER TABLE server DROP star_url');
            $this->addSql('ALTER TABLE server DROP star_server_id');
        }
    }
}
