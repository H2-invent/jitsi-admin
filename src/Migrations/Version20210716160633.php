<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20210716160633 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE address_group (id INT AUTO_INCREMENT NOT NULL, leader_id INT NOT NULL, name LONGTEXT NOT NULL, created_at DATETIME NOT NULL COMMENT \'(DC2Type:datetime_immutable)\', updated_at DATETIME DEFAULT NULL COMMENT \'(DC2Type:datetime_immutable)\', INDEX IDX_5A6533E573154ED4 (leader_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('CREATE TABLE address_group_user (address_group_id INT NOT NULL, user_id INT NOT NULL, INDEX IDX_DC5405A7DB27C6 (address_group_id), INDEX IDX_DC5405A7A76ED395 (user_id), PRIMARY KEY(address_group_id, user_id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE address_group ADD CONSTRAINT FK_5A6533E573154ED4 FOREIGN KEY (leader_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE address_group_user ADD CONSTRAINT FK_DC5405A7DB27C6 FOREIGN KEY (address_group_id) REFERENCES address_group (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE address_group_user ADD CONSTRAINT FK_DC5405A7A76ED395 FOREIGN KEY (user_id) REFERENCES fos_user (id) ON DELETE CASCADE');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE address_group_user DROP FOREIGN KEY FK_DC5405A7DB27C6');
            $this->addSql('DROP TABLE address_group');
            $this->addSql('DROP TABLE address_group_user');
        }
    }
}
