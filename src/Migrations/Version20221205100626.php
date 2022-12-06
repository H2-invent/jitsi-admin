<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221205100626 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE SEQUENCE log_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
            $this->addSql('CREATE TABLE log (id INT NOT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, user_name TEXT NOT NULL, message TEXT NOT NULL, PRIMARY KEY(id))');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP SEQUENCE log_id_seq CASCADE');
            $this->addSql('DROP TABLE log');
        }
    }
}
