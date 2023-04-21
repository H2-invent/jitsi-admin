<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220329070213 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE caller_id ADD caller_session_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE caller_id ADD CONSTRAINT FK_A5626C526D04C84F FOREIGN KEY (caller_session_id) REFERENCES caller_session (id)');
            $this->addSql('CREATE UNIQUE INDEX UNIQ_A5626C526D04C84F ON caller_id (caller_session_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE caller_id DROP FOREIGN KEY FK_A5626C526D04C84F');
            $this->addSql('DROP INDEX UNIQ_A5626C526D04C84F ON caller_id');
            $this->addSql('ALTER TABLE caller_id DROP caller_session_id');
        }
    }
}
