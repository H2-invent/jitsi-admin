<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220714070503 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE SEQUENCE star_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
            $this->addSql('CREATE TABLE star (id INT NOT NULL, server_id INT NOT NULL, star INT NOT NULL, comment TEXT DEFAULT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_C9DB5A141844E6B7 ON star (server_id)');
            $this->addSql('ALTER TABLE star ADD CONSTRAINT FK_C9DB5A141844E6B7 FOREIGN KEY (server_id) REFERENCES server (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP SEQUENCE star_id_seq CASCADE');
            $this->addSql('DROP TABLE star');
        }
    }
}
