<?php

declare(strict_types=1);

namespace DoctrineMigrations;

use Doctrine\DBAL\Schema\Schema;
use Doctrine\Migrations\AbstractMigration;

/**
 * Auto-generated Migration: Please modify to your needs!
 */
final class Version20220307115326 extends AbstractMigration
{
    public function getDescription(): string
    {
        return '';
    }

    public function up(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this up() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F64B09E92C');
            $this->addSql('ALTER TABLE server CHANGE administrator_id administrator_id INT DEFAULT NULL');
            $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F64B09E92C FOREIGN KEY (administrator_id) REFERENCES fos_user (id) ON DELETE SET NULL');
        }
    }

    public function down(Schema $schema): void
    {
        if ($this->connection->getDatabasePlatform()->getName() !== 'postgresql') {
            // this down() migration is auto-generated, please modify it to your needs
            $this->addSql('ALTER TABLE server DROP FOREIGN KEY FK_5A6DD5F64B09E92C');
            $this->addSql('ALTER TABLE server CHANGE administrator_id administrator_id INT NOT NULL');
            $this->addSql('ALTER TABLE server ADD CONSTRAINT FK_5A6DD5F64B09E92C FOREIGN KEY (administrator_id) REFERENCES fos_user (id)');
        }
    }
}
