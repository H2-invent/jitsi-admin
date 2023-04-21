<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210319075734 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE keycloak_groups_to_servers (id INT AUTO_INCREMENT NOT NULL, server_id INT NOT NULL, keycloak_group LONGTEXT NOT NULL, INDEX IDX_A15849ED1844E6B7 (server_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE keycloak_groups_to_servers ADD CONSTRAINT FK_A15849ED1844E6B7 FOREIGN KEY (server_id) REFERENCES server (id)');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE keycloak_groups_to_servers');
        }
    }
}
