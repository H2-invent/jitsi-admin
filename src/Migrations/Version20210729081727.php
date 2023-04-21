<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210729081727 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE ldap_user_properties (id INT AUTO_INCREMENT NOT NULL, user_id INT NOT NULL, ldap_host LONGTEXT NOT NULL, ldap_dn LONGTEXT NOT NULL, UNIQUE INDEX UNIQ_ACEA2AF5A76ED395 (user_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE ldap_user_properties ADD CONSTRAINT FK_ACEA2AF5A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE fos_user DROP ldap_dn, DROP ldap_host');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('DROP TABLE ldap_user_properties');
            $this->addSql('ALTER TABLE fos_user ADD ldap_dn LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`, ADD ldap_host LONGTEXT CHARACTER SET utf8mb4 DEFAULT NULL COLLATE `utf8mb4_unicode_ci`');
        }
    }
}
