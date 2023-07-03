<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221205163204 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE log ADD user_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE log ADD CONSTRAINT FK_8F3F68C5A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('CREATE INDEX IDX_8F3F68C5A76ED395 ON log (user_id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE SCHEMA public');
            $this->addSql('ALTER TABLE log DROP CONSTRAINT FK_8F3F68C5A76ED395');
            $this->addSql('DROP INDEX IDX_8F3F68C5A76ED395');
            $this->addSql('ALTER TABLE log DROP user_id');
        }
    }
}
