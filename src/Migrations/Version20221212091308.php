<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20221212091308 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('CREATE SEQUENCE deputy_id_seq INCREMENT BY 1 MINVALUE 1 START 1');
            $this->addSql('CREATE TABLE deputy (id INT NOT NULL, deputy_id INT NOT NULL, manager_id INT NOT NULL, is_from_ldap BOOLEAN DEFAULT NULL, created_at TIMESTAMP(0) WITHOUT TIME ZONE NOT NULL, PRIMARY KEY(id))');
            $this->addSql('CREATE INDEX IDX_28FA6B9F4B6F93BB ON deputy (deputy_id)');
            $this->addSql('CREATE INDEX IDX_28FA6B9F783E3463 ON deputy (manager_id)');
            $this->addSql('ALTER TABLE deputy ADD CONSTRAINT FK_28FA6B9F4B6F93BB FOREIGN KEY (deputy_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE deputy ADD CONSTRAINT FK_28FA6B9F783E3463 FOREIGN KEY (manager_id) REFERENCES fos_user (id) NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE deputy_manager DROP CONSTRAINT fk_b4c5243c3ad8644e');
            $this->addSql('ALTER TABLE deputy_manager DROP CONSTRAINT fk_b4c5243c233d34c1');
            $this->addSql('DROP TABLE deputy_manager');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() == 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs

            $this->addSql('DROP SEQUENCE deputy_id_seq CASCADE');
            $this->addSql('CREATE TABLE deputy_manager (user_source INT NOT NULL, user_target INT NOT NULL, PRIMARY KEY(user_source, user_target))');
            $this->addSql('CREATE INDEX idx_b4c5243c233d34c1 ON deputy_manager (user_target)');
            $this->addSql('CREATE INDEX idx_b4c5243c3ad8644e ON deputy_manager (user_source)');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT fk_b4c5243c3ad8644e FOREIGN KEY (user_source) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE deputy_manager ADD CONSTRAINT fk_b4c5243c233d34c1 FOREIGN KEY (user_target) REFERENCES fos_user (id) ON DELETE CASCADE NOT DEFERRABLE INITIALLY IMMEDIATE');
            $this->addSql('ALTER TABLE deputy DROP CONSTRAINT FK_28FA6B9F4B6F93BB');
            $this->addSql('ALTER TABLE deputy DROP CONSTRAINT FK_28FA6B9F783E3463');
            $this->addSql('DROP TABLE deputy');
        }
    }
}
