<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221212090934 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE deputy (id INT AUTO_INCREMENT NOT NULL, deputy_id INT NOT NULL, manager_id INT NOT NULL, is_from_ldap TINYINT(1) DEFAULT NULL, created_at DATETIME NOT NULL, INDEX IDX_28FA6B9F4B6F93BB (deputy_id), INDEX IDX_28FA6B9F783E3463 (manager_id), PRIMARY KEY(id)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB');
            $this->addSql('ALTER TABLE deputy ADD CONSTRAINT FK_28FA6B9F4B6F93BB FOREIGN KEY (deputy_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE deputy ADD CONSTRAINT FK_28FA6B9F783E3463 FOREIGN KEY (manager_id) REFERENCES fos_user (id)');
            $this->addSql('ALTER TABLE deputy_manager DROP FOREIGN KEY FK_B4C5243C3AD8644E');
            $this->addSql('ALTER TABLE deputy_manager DROP FOREIGN KEY FK_B4C5243C233D34C1');
            $this->addSql('DROP TABLE deputy_manager');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE TABLE deputy_manager (user_source INT NOT NULL, user_target INT NOT NULL, INDEX IDX_B4C5243C233D34C1 (user_target), INDEX IDX_B4C5243C3AD8644E (user_source), PRIMARY KEY(user_source, user_target)) DEFAULT CHARACTER SET utf8mb4 COLLATE `utf8mb4_unicode_ci` ENGINE = InnoDB COMMENT = \'\' ');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT FK_B4C5243C3AD8644E FOREIGN KEY (user_source) REFERENCES fos_user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT FK_B4C5243C233D34C1 FOREIGN KEY (user_target) REFERENCES fos_user (id) ON DELETE CASCADE');
            $this->addSql('ALTER TABLE deputy DROP FOREIGN KEY FK_28FA6B9F4B6F93BB');
            $this->addSql('ALTER TABLE deputy DROP FOREIGN KEY FK_28FA6B9F783E3463');
            $this->addSql('DROP TABLE deputy');
        }
    }
}
