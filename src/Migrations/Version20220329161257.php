<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329161257 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE caller_session ADD show_name LONGTEXT DEFAULT NULL');
            $this->addSql('ALTER TABLE lobby_waitung_user ADD caller_session_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE lobby_waitung_user ADD CONSTRAINT FK_6ABDB21A6D04C84F FOREIGN KEY (caller_session_id) REFERENCES caller_session (id) ON DELETE SET NULL');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_6ABDB21A6D04C84F ON lobby_waitung_user (caller_session_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE caller_session DROP show_name');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP FOREIGN KEY FK_6ABDB21A6D04C84F');
            $this->addSql('DROP INDEX UNIQ_6ABDB21A6D04C84F ON lobby_waitung_user');
            $this->addSql('ALTER TABLE lobby_waitung_user DROP caller_session_id');
        }
    }
}
