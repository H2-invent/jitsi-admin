<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220830163418 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform === false) {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server ADD star_url LONGTEXT DEFAULT NULL, ADD star_server_id INT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform() instanceof \Doctrine\DBAL\Platforms\PostgreSQLPlatform === false) {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP star_url, DROP star_server_id');
        }
    }
}
