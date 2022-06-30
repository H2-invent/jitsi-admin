<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210902081857 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'mysql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE users (id INT AUTO_INCREMENT NOT NULL, PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE ldap_user_properties ADD rdn LONGTEXT DEFAULT NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'mysql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE users');
            $this->addSql('ALTER TABLE ldap_user_properties DROP rdn');
        }
    }
}
